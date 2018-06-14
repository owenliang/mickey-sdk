<?php
namespace cat;

// 管理本地调用的构建与发送
class Manager
{
    // 发送日志给CAT
    private $sender;

    // 分布式调用链上下文
    private $context;

    // 构建本地transaction树
    private $builder;

    public function __construct($context)
    {
        $this->context = $context;
        $this->builder = new Builder($this);
    }

    // 结束最近一个事务
    public function endTransaction($status, $data)
    {
        $rootTran = $this->builder->endTransaction($status, $data);
        if ($rootTran) {
            // $this->>sender->buildAndSend($context, $rootTran);
        }
    }

    // message可以是transaction, event
    public function addMessage($message)
    {
        $this->builder->addMessage($message);
    }

    // 获取上下文信息

    // 分配message id
    public function generateMessageId()
    {
        $hexIp = dechex(ip2long($this->context->ip));
        $hour = intval(time() / 3600);

        $semKey = crc32('CAT-COUNTER-LOCK:' . $this->context->domain);
        $shmKey = crc32('CAT-COUNTER-SHM:' . $this->context->domain);

        $sem = \sem_get($semKey);
        $shm = \shm_attach($shmKey, 1 * 1024); // 1KB

        \sem_acquire($sem); // 上锁

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

        return "{$this->context->domain}-{$hexIp}-{$hour}-{$counter}";
    }
}