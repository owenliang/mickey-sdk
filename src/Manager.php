<?php
namespace cat;

// 管理本地调用的构建与发送
class Manager
{
    // 序列化本地调用链
    private $codec;

    // 发送日志给CAT
    private $sender;

    // 分布式调用链上下文
    private $context;

    // 构建本地transaction树
    private $builder;

    // CAT基础配置
    private $config = [
        'routerTTL' => 3600, // 每小时重新拉取一次cat服务器地址
        'routerApi' => 'http://localhost/cat/s/router',  // 获取日志上报地址
        'tcpTimeout' => 2000, // 上报超时
    ];

    public function __construct($config = [])
    {
        $this->codec = new Codec($this);
        $this->builder = new Builder($this);
        $this->sender = new Sender($this);
        $this->config = array_merge($this->config, $config);
    }

    // 获取CAT配置
    public function getConfig()
    {
        return $this->config;
    }

    // 设置服务端上下文
    public function setServerContext($context)
    {
        $this->context = $context;
    }

    // 获取服务端上下文
    public function getServerContext()
    {
        return $this->context;
    }

    // 获取客户端上下文
    public function getClientContext()
    {
        $curTran = $this->builder->curTransaction();
        $context = new Context();
        $context->catChildMessageId = $curTran->messageId;
        $context->catParentMessageId = $this->context->catChildMessageId;
        $context->catRootMessageId = $this->context->catRootMessageId;
        return $context;
    }

    // 结束最近一个事务
    public function endTransaction($status, $data)
    {
        $rootTran = $this->builder->endTransaction($status, $data);
        if ($rootTran) {
            $catData = $this->codec->encode($rootTran);
            $this->sender->send($catData);
        }
    }

    // message可以是transaction, event
    public function addMessage($message)
    {
        $this->builder->addMessage($message);
    }

    // 分配message id
    public function generateMessageId()
    {
        $semKey = crc32('CAT-COUNTER-LOCK:' . $this->context->domain);
        $shmKey = crc32('CAT-COUNTER-SHM:' . $this->context->domain);

        $sem = \sem_get($semKey);
        $shm = \shm_attach($shmKey, 1 * 1024); // 1KB

        \sem_acquire($sem); // 上锁

        $hour = intval(time() / 3600);

        // 0: 当前计数所属的小时, 1:当前计数的值
        if (!\shm_has_var($shm, 0) ||
            !\shm_has_var($shm, 1) ||
            \shm_get_var($shm, 0) != $hour) {
            // 重置计数
            \shm_put_var($shm, 0, $hour);
            \shm_put_var($shm, 1, 0);
        }

        // 获取下一个计数
        $counter = \shm_get_var($shm, 1);
        \shm_put_var($shm, 1, $counter + 1);

        \sem_release($sem); //  放锁

        $hexIp = dechex(ip2long($this->context->ip));

        return "{$this->context->domain}-{$hexIp}-{$hour}-{$counter}";
    }
}