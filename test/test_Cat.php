<?php

require_once __DIR__ . '/../vendor/autoload.php';

$context= new \cat\Context();

$context->domain = 'mickey.sdk';
$context->hostname = gethostname();
$context->ip = "127.0.0.1";

$manager = new \cat\Manager(['routerApi' => 'http://cat-itoamms.smzdm.com/cat/s/router']);
$producer = new \cat\Producer($manager);
$manager->setServerContext($context);

$context->catRootMessageId =  $context->catChildMessageId = $manager->generateMessageId();

$producer->startTransaction('URL', '/index');

$producer->logEvent('FROM', 'mickey');

$producer->endTransaction();