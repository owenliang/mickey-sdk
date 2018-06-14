<?php

// transaction可以嵌套transaction
class Transaction extends Message
{
    // 内部的event或者嵌套transaction
    public $children = [];

    public function __construct($type, $name, $data = [])
    {
        parent::__construct($type, $name, self::SUCCESS, $data);
    }
}