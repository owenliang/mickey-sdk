<?php

require_once __DIR__ . '/../vendor/autoload.php';

$context= new \cat\Context();

$context->domain = 'mickey.sdk';
$context->hostname = gethostname();
$context->ip = "127.0.0.1";

$manager = new \cat\Manager(['routerApi' => 'http://cat-itoamms.smzdm.com/cat/s/router']);
$producer = new \cat\Producer($manager);
$manager->setServerContext($context);

$producer->startTransaction('URL', '/');

$producer->startTransaction('Redis', 'GET', ['key' => 'user:110']);
$producer->logEvent('Redis.addr', 'mickey-cache.com');

// 上下文可以注入到RPC中去
$clientContext = $manager->getClientContext();

$producer->endTransaction();

$producer->endTransaction();