<?php

require_once __DIR__ . "/Message.php";

// transaction可以嵌套transaction
class Transaction extends Message
{
    public function __construct($type, $name, $data = [])
    {
        parent::__construct($type, $name, self::SUCCESS, $data);
    }
}