<?php
namespace cat;

// TCP发送日志给CAT服务端
class Sender
{
    private $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    private function chooseCatServer()
    {
        $config = $this->manager->getConfig();
        $context = $this->manager->getServerContext();

        $semKey = crc32('CAT-SERVER-LOCK:' . $context->domain);
        $shmKey = crc32('CAT-SERVER-SHM:' . $context->domain);

        $sem = \sem_get($semKey);
        $shm = \shm_attach($shmKey, 64 * 1024); // 64KB

        \sem_acquire($sem); // 上锁

        $timestamp = time();

        // 0: 地址刷新的时间, 1: 地址列表, 2: 轮转计数
        if (!\shm_has_var($shm, 0) ||
            !\shm_has_var($shm, 1) ||
            !\shm_has_var($shm, 2) ||
            $timestamp - \shm_get_var($shm, 0) >= $config['routerTTL']) {

            \sem_release($sem); // 放锁

            // 拉取提交地址列表
            $query = http_build_query(['ip' => $context->ip, 'domain' => $context->domain, 'op' => 'json']);
            $resp = file_get_contents($config['routerApi'] . "?{$query}", false, stream_context_create(['http' => ['timeout' => 2]]));
            $resp = @json_decode($resp, true);
            if (!empty($resp) && !empty($resp['kvs']['routers'])) {
                $resp['kvs']['routers'] = explode(';', $resp['kvs']['routers']);
                $serverList = [];
                foreach ($resp['kvs']['routers'] as $server) {
                    $server = trim($server);
                    if (!empty($server)) {
                        list($ip, $port) = explode(':', $server);
                        $serverList[] = ['ip' => $ip, 'port' => $port];
                    }
                }
                if (!empty($serverList)) {
                    \sem_acquire($sem); // 重新上锁
                    shm_put_var($shm, 0, time());
                    shm_put_var($shm, 1, json_encode($serverList));
                    shm_put_var($shm, 2, 0);
                }
            }
        }

        // 异常覆盖
        if (!shm_has_var($shm, 2) || shm_get_var($shm, 2) < 0) {
            shm_put_var($shm, 2, 0);
        }

        $addr = false;

        // 挑选1个提交地址
        if (shm_has_var($shm, 1)) {
            $addrs = @json_decode(shm_get_var($shm, 1), true);
            if (!empty($addrs)) {
                $counter = shm_get_var($shm, 2);
                $addr = $addrs[$counter % count($addrs)];
                shm_put_var($shm, 2, $counter + 1);
            }
        }
        \sem_release($sem); //  放锁

        return $addr;
    }

    private function curTimestampMs()
    {
        return intval(microtime(true) * 1000);
    }

    private function connect($ip, $port, &$timeoutMs)
    {
        // 非阻塞socket
        $socket = \socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if (empty($socket)) {
            return false;
        }
        \socket_set_nonblock($socket);

        // 非阻塞连接
        if (!\socket_connect($socket, $ip, $port)) {
            if  (socket_last_error($socket) != SOCKET_EINPROGRESS) {
                goto FAIL;
            }
            socket_clear_error($socket);
        } else {
            return $socket;
        }

        // 开始时间
        $st = $this->curTimestampMs();

        do {
            $timeUsed = $this->curTimestampMs() - $st;
            if ($timeUsed >= $timeoutMs) {
                goto FAIL;
            }
            $waitTime = $timeoutMs - $timeUsed;

            // 事件循环
            $r = null;
            $w = [$socket];
            $e = [$socket];

            $n = \socket_select($r, $w, $e, intval($waitTime / 1000), ($waitTime % 1000) * 1000);
            if ($n === false) {
                if (\socket_last_error() != SOCKET_EINTR) {  // 出致命错误
                    goto FAIL;
                }
            } else if ($n === 0) { // 超时
                goto FAIL;
            } else { // 发生了事件
                if (socket_last_error($socket) != 0) { // 发生错误事件
                    goto FAIL;
                }
                break;
            }
        } while (1);

        $timeoutMs -= $this->curTimestampMs() - $st; // 减去耗时
        return $socket;

        FAIL:
        \socket_close($socket);
        return false;
    }

    private function write($socket, $data, &$timeoutMs)
    {
        // 已发出的字节数
        $outLen = 0;

        // 开始时间
        $st = $this->curTimestampMs();

        while ($outLen < strlen($data)) {
            $timeUsed = $this->curTimestampMs() - $st;
            if ($timeUsed >= $timeoutMs) {
                goto FAIL;
            }
            $waitTime = $timeoutMs - $timeUsed;

            // 事件循环
            $r = null;
            $w = [$socket];
            $e = [$socket];

            $n = \socket_select($r, $w, $e, intval($waitTime / 1000), ($waitTime % 1000) * 1000);
            if ($n === false) {
                if (\socket_last_error() != SOCKET_EINTR) {  // 出致命错误
                    goto FAIL;
                }
            } else if ($n === 0) { // 超时
                goto FAIL;
            } else { // 发生了事件
                if (!in_array($socket, $w)) { // 发生了错误
                    goto FAIL;
                }
                $out = socket_write($socket, substr($data, $outLen));
                if ($out === false) {
                    if (socket_last_error($socket) != SOCKET_EAGAIN) {
                        goto FAIL;
                    }
                } else {
                    $outLen += $out;
                }
                break;
            }
        }

        $timeoutMs -= $this->curTimestampMs() - $st; // 减去耗时
        return true;

        FAIL:
        return false;
    }

    public function send($catData)
    {
        // 获取1个CAT上报服务器
        $addr = $this->chooseCatServer();

        if (!empty($addr)) {
            $config = $this->manager->getConfig();
            $timeoutMs = $config['tcpTimeout'];

            // 连接
            $socket = $this->connect($addr['ip'], $addr['port'], $timeoutMs);
            if (empty($socket)) {
                return;
            }

            // 发送
            if ($this->write($socket, $catData, $timeoutMs)) {
               // echo $catData;
            }
            socket_close($socket);
        }
    }
}