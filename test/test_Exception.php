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
$context->catChildMessageId = $manager->generateMessageId('mickey.smzdm.com');

// CAT消息构造
$producer = new \cat\Producer($manager);

// 以type=URL的transaction是服务端transaction
$producer->startTransaction('URL', '/api/user/{}/logout');    // /api/user/32124/logout中的数字323142需要打码成{}

$producer->logEvent('URL', 'URL.Server', 0, 'http://mickey.smzdm.com:8080');
$producer->logEvent('URL', 'URL.Method', 0, 'GET /api/user/323142/logout');

// Exception会被CAT作为异常处理，进入错误大盘
// Event的status!=0会进入Problem视图的error
$producer->logEvent('Exception', 'NullException', '错误原因:yyyy');

$producer->endTransaction();