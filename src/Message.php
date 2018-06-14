<?php
namespace cat;

// Transaction, Event, Metrics...的基类
class Message
{
    const SUCCESS = '0';

    public $type;
    public $name;
    public $timestamp;
    public $status;
    public $data;

    public function __construct($type, $name, $status = self::SUCCESS, $data = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->timestamp = intval(microtime(true) * 1000);
        $this->status = $status;
        $this->data = $data;
    }
}