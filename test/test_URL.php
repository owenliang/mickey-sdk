<?php

require_once __DIR__ . '/../vendor/autoload.php';

// 服务端上下文
$context= new \cat\Context();
$context->domain = 'mickey.smzdm.com';
$context->hostname = \cat\Util::getHostname();
$context->ip = \cat\Util::getLocalIp();

// CAT核心
$manager = new \cat\Manager(['routerApi' => 'http://mickey.smzdm.com:8080/cat/s/router']);
$manager->setServerContext($context);

// 没有服务端Span, 我们自己生成一个root span的标示, parent span为空
$context->catChildMessageId = $manager->generateMessageId();

// CAT消息构造
$producer = new \cat\Producer($manager);

// 以type=URL的transaction是服务端transaction
$producer->startTransaction('URL', '/api/user/{}/info');    // /api/user/32124/info中的数字323142需要打码成{}

$producer->logEvent('URL', 'URL.Server', 0, 'http://mickey.smzdm.com:8080');
$producer->logEvent('URL', 'URL.Method', 0, 'GET /api/user/323142/gold');

// 可以让RPC CLIENT端在Header中携带其自身地址，以便可以在RPC SERVER端打印出上游是谁
// $producer->logEvent('URL', 'URL.From', 0, 'http://www.baidu.com');

// 模拟处理1秒，造成一个long-url
sleep(1);

$producer->endTransaction();