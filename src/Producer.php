<?php

require_once __DIR__ . "/Message.php";
require_once __DIR__ . "/Transaction.php";
require_once __DIR__ . "/Event.php";
require_once __DIR__ . "/Manager.php";

class Producer
{
    private $manager;

    public function __construct()
    {
        $this->manager = new Manager();
    }

    public function logEvent($type, $name, $status = Message::SUCCESS, $data = [])
    {
        $event = new Event($type, $name, $status, $data);
    }

    public function startTransaction($type, $name, $data = [])
    {
        $tran = new Transaction($type, $name, $data);
    }

    public function endTransaction($status = Message::SUCCESS, $data = [])
    {
        $this->manager->endTransaction();
    }
}