<?php
namespace cat;

// 序列化整个本地调用链
class Codec
{
    private $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function encode($rootTran)
    {
        return json_encode($rootTran);
    }
}