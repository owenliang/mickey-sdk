<?php

class Context
{
    // 维护当前所在的transaction栈关系
    private $transStack = [];

    public function __construct()
    {
    }

    public function addMessage($message)
    {
        if ($message instanceof Transaction) {
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