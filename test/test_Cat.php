<?php

require_once __DIR__ . '/../vendor/autoload.php';

$context= new \cat\Context();

$context->domain = 'mickey-sdk';
$context->hostname = gethostname();
$context->ip = "127.0.0.1";

$manager = new \cat\Manager($context);

$producer = new \cat\Producer($manager);

$producer->startTransaction('URL', '/');

var_dump($manager);