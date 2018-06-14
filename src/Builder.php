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
            $this->transStack[] = $message;
        } else {
            $curTran = $this->transStack[count($this->transStack) - 1];
            $curTran->children[] = $message;
        }
    }

    public function endTransaction($status, $data)
    {
        $transCount = count($this->transStack);
        if ($transCount) {
            $tran = $this->transStack[$transCount - 1];
            $tran->status = $status;
            $tran->data = array_merge_recursive($tran->data, $data);
            array_pop($this->transStack);
            if (!count($this->transStack)) {    // root transaction已弹出, 准备发送给cat
                return $tran;
            }
        }
        return false;
    }
}