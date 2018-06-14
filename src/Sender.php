<?php
namespace cat;

// TCP发送日志给CAT服务端
class Sender
{
    private $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function send($catData)
    {
        echo $catData . PHP_EOL;
    }

}