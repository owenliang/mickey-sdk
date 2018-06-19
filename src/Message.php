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

    // transaction是否完成, 其他的都是原子性的
    private $complete = false;

    public function __construct($type, $name, $status = self::SUCCESS, $data = [])
    {
        $this->type = $type;
        $this->name = $name;
        $this->timestamp = intval(microtime(true) * 1000 * 1000);
        $this->status = $status;
        $this->data = $data;
    }

    public function complete()
    {
        $this->complete = true;
    }

    public function isCompleted()
    {
        return $this->complete;
    }
}