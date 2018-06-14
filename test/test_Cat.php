<?php

require_once __DIR__ . '/../vendor/autoload.php';

$context= new \cat\Context();

$context->domain = 'mickey.sdk';
$context->hostname = gethostname();
$context->ip = "127.0.0.1";

$manager = new \cat\Manager();
$producer = new \cat\Producer($manager);
$manager->setServerContext($context);

$producer->startTransaction('URL', '/');

$producer->startTransaction('Redis', 'GET', ['key' => 'user:110']);
$producer->logEvent('Redis.addr', 'mickey-cache.com');

$clientContext = $manager->getClientContext();
var_dump($clientContext);

$producer->endTransaction();

$producer->endTransaction();