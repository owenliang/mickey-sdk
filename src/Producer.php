<?php
namespace cat;

// 重要提示
// status=0为正常，status!=0是异常（可以是任意非0值，比如一个字符串），会触发CAT报警
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
        $event->complete();
        $this->manager->addMessage($event);
    }

    // name: 统计项
    // status: 统计方式(C:count  S: sum  S,C:multiple sum, T:duration)
    // data: 累加值(其中S与S,C都是double, C是整形, T是毫秒)
    public function logMetric($name, $status, $data)
    {
        $metric = new Metric('', $name, $status, $data);
        $metric->complete();
        $this->manager->addMessage($metric);
    }

    public function startTransaction($type, $name, $data = [])
    {
        $tran = new Transaction($type, $name, $data);
        $this->manager->addMessage($tran);
    }

    public function endTransaction($status = Message::SUCCESS, $data = [])
    {
        $this->manager->endTransaction($status, $data);
    }
}