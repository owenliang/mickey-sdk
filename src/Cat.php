<?php
namespace cat;

class Cat
{
    private static $instance = null;
    private $domain = null;
    private $producer = null;
    private $manager = null;

    public function __construct($domain = "")
    {
        $this->setDomain($domain);
        $this->init();
    }

    public static function initInstance($domain = "default.domain.com")
    {
        if (is_null(self::$instance)) {
            self::$instance = new Cat($domain);
        }

        return self::$instance;
    }

    #初始化上下文数据
    public function init()
    {
        $context = new \cat\Context();
        $context->domain = $this->getDomain();
        $context->hostname = \cat\Util::getHostname();
        $context->ip = \cat\Util::getLocalIp();

        $manager = new \cat\Manager(['routerApi' => 'http://mickey.smzdm.com:8080/cat/s/router']);
        $manager->setServerContext($context);

        $this->setManager($manager);

        $producer = new \cat\Producer($manager);

        $this->setProducer($producer);

        $context->catChildMessageId = $manager->generateMessageId($this->getDomain());

    }

    //TODO 需要修正
    public static function checkAndInitialize()
    {
        if (is_null(self::$instance)) {
            return null;
        }
    }

    public function setDomain($domain = "")
    {
        $this->domain = $domain;
    }

    public function getDomain()
    {
        return $this->domain;
    }

    public function setProducer($producer = null)
    {
        $this->producer = $producer;
    }

    public function getProducer()
    {
        return $this->producer;
    }

    public function setManager($manager = null)
    {
        $this->manager = $manager;
    }

    public function getManager()
    {
        return $this->manager;
    }

    public function initMessageId($manager = [])
    {

    }

    /**
     * 获取父URL数据
     */
    public function getParentInfo()
    {

    }

    public static function logError()
    {

    }

    //event
    public function logEvent($type, $name, $status = Message::SUCCESS, $data = [])
    {
        $this->getProducer()->logEvent($type, $name, $status, $data);
    }

    //事务
    public function startTransaction($type, $name, $data = [])
    {
        $this->getProducer()->startTransaction($type, $name, $data);
    }

    //内部远程调用，使用Call, 需要配合使用 RemoteCall event
    public function startTransactionCall($name, $data = [])
    {
        $this->getProducer()->startTransaction('Call', $name, $data);
    }

    public function startTransactionURL($name, $data = [])
    {
        $this->getProducer()->startTransaction('URL', $name, $data);
    }

    public function endTransaction($status = Message::SUCCESS, $data = [])
    {
        $this->getProducer()->endTransaction($status, $data);
    }

    /**
     * @param $name
     * @param $data double
     */
    public function logMetricForCount($name, $value)
    {
        $this->getProducer()->logMetric($name, 'C',  $value);
    }

    /**
     * @param $name
     * @param int $durationInMillis 毫秒
     */
    public function logMetricForDuration($name, $durationInMillis = 0)
    {
        $this->getProducer()->logMetric($name, 'T',  $durationInMillis);
    }

    /**
     * @param $name
     * @param $value int
     */
    public function logMetricForSum($name, $value)
    {
        $this->getProducer()->logMetric($name, 'S',  $value);
    }

    /**
     * @param $name
     * @param $sum double
     * @param $quantity int
     */
    public function logMetricForSumDuration($name, $sum, $quantity)
    {
        $value = sprintf("%s,%.2f", $quantity, $sum);
        $this->getProducer()->logMetric($name, 'S,C',  $value);
    }


}