<?php
namespace cat;

// transaction内可以记录event
class Event extends Message
{
    public function __construct($type, $name, $status = self::SUCCESS, $data = [])
    {
        parent::__construct($type, $name, $status, $data);
    }
}