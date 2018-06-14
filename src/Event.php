<?php

require_once __DIR__ . "/Message.php";

// transaction内可以记录event
class Event extends Message
{
    public function __construct($type, $name, $status = self::SUCCESS, $data = [])
    {
        parent::__construct($type, $name, $status, $data);
    }
}