<?php

require_once __DIR__ . '/../vendor/autoload.php';

$context= new \cat\Context();

$context->domain = 'mickey.smzdm.com';
$context->hostname = \cat\Util::getHostname();
$context->ip = \cat\Util::getLocalIp();

$manager = new \cat\Manager(['routerApi' => 'http://mickey.smzdm.com:8080/cat/s/router']);
$producer = new \cat\Producer($manager);
$manager->setServerContext($context);

$context->catChildMessageId = $manager->generateMessageId();

$producer->startTransaction('URL', '/api/demo');

$producer->logEvent('FROM', 'Request.from', 0, '	/api/look <= api.smzdm.com/v1/util/map/geocode');

$producer->startTransaction('REDIS', 'GET');
//$producer->logEvent('Exception', "一个大异常", '123123123123', 'localhost:6379');
$producer->logMetric('文章打分', 'C', 1000); // 分钟级metric累加1
$producer->endTransaction();

$producer->endTransaction();