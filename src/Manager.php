<?php

// 管理整个MsgTree
class Manager
{
    // 发送日志给CAT
    private $sender;

    // 分布式调用链上下文
    private $msgTree;

    // 构建MsgTree的所有上下文信息
    private $context;

    public function __construct($msgTree)
    {
        $this->msgTree = $msgTree;
        $this->context = new Context();
    }

    // 结束最近一个事务
    public function endTransaction($status, $data)
    {
        $rootTran = $this->context->endTransaction();
        if ($rootTran) {
            // $this->>sender->buildAndSend($rootTran);
        }
    }

    // message可以是transaction, event
    public function addMessage($message)
    {
        $this->context->addMessage($message);
    }
}