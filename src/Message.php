<?php
namespace cat;

// Transaction, Event, Metrics...的基类
class Message
{
    const SUCCESS = '0';

    private $type;
    private $name;
    private $timestamp;
    private $status;
    private $data;

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

    // 数组则合并, 否则覆盖
    public function setData($data)
    {
        if (is_array($this->data) && is_array($data)) {
            $this->data = array_merge_recursive($this->data, $data);
        } else {
            $this->data = $data;
        }
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function __get($name)
    {
        return $this->$name;
    }
}