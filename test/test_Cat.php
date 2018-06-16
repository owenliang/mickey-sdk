<?php

require_once __DIR__ . '/../vendor/autoload.php';

$context= new \cat\Context();

$context->domain = 'metric.hudong';
$context->hostname = \cat\Util::getHostname();
$context->ip = \cat\Util::getLocalIp();

$manager = new \cat\Manager(['routerApi' => 'http://cat-itoamms.smzdm.com/cat/s/router']);
$producer = new \cat\Producer($manager);
$manager->setServerContext($context);

$context->catChildMessageId = $manager->generateMessageId();

$producer->startTransaction('URL', '/api/redis-7776');

$producer->logEvent('FROM', 'Request.from', '	/api/look <= api.smzdm.com/v1/util/map/geocode');

$producer->startTransaction('REDIS', 'GET');
$producer->logEvent('REDIS', "ADDR", 0, 'localhost:6379');
$producer->logMetric('文章打分', 'C', 1000); // 分钟级metric累加1
$producer->endTransaction();

$producer->endTransaction();