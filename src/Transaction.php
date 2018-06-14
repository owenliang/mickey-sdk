<?php
namespace cat;

// transaction可以嵌套transaction
class Transaction extends Message
{
    // 内部的event或者嵌套transaction
    public $children = [];

    // 事务唯一ID
    public $messageId;

    // 耗时
    public $duration;

    public function __construct($type, $name, $data = [])
    {
        parent::__construct($type, $name, self::SUCCESS, $data);
    }
}