<?php
namespace cat;

// 构造本地调用树
class Builder
{
    // 维护当前所在的transaction栈关系
    private $transStack = [];

    private $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function addMessage($message)
    {
        if ($message instanceof Transaction) {
            $message->messageId = $this->manager->generateMessageId(); // 事务唯一ID
        } else if (!count($this->transStack)) { // 普通消息必须嵌套在事务里
            return;
        }

        // 嵌套到前一个事务里
        if (count($this->transStack)) {
            $this->transStack[count($this->transStack) - 1]->children[] = $message;
        }
        // 如果是事务，那么作为新的上下文
        if ($message instanceof Transaction) {
            $this->transStack[] = $message;
        }
    }

    public function endTransaction($status, $data)
    {
        $tran = $this->transStack[count($this->transStack) - 1];
        $tran->duration = intval(microtime(true) * 1000 * 1000) - $tran->timestamp;
        $tran->status = $status;
        $tran->data = array_merge_recursive($tran->data, $data);
        array_pop($this->transStack);
        if (!count($this->transStack)) {    // root transaction已弹出, 准备发送给cat
            return $tran;
        }
        return false;
    }

    public function curTransaction()
    {
        return $this->transStack[count($this->transStack) - 1];
    }
}