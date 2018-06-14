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

    public function send($catData)
    {
        // 获取1个CAT上报服务器
        $addr = $this->chooseCatServer();

        // TCP上报
        var_dump($addr);
    }
}