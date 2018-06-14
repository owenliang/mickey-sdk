<?php
namespace cat;

class Producer
{
    private $manager;

    public function __construct($manager)
    {
        $this->manager = $manager;
    }

    public function logEvent($type, $name, $status = Message::SUCCESS, $data = [])
    {
        $event = new Event($type, $name, $status, $data);
        $this->manager->addMessage($event);
    }

    public function startTransaction($type, $name, $data = [])
    {
        $tran = new Transaction($type, $name, $data);
        $this->manager->addMessage($tran);
    }

    public function endTransaction($status = Message::SUCCESS, $data = [])
    {
        $this->manager->endTransaction();
    }
}