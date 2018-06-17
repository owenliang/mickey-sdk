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

// 启动最外层服务端transaction
$producer->startTransaction('URL', '/api/user/{}/login');    // /api/user/32124/login中的数字323142需要打码成{}

$producer->logEvent('URL', 'URL.Server', 0, 'http://mickey.smzdm.com:8080');
$producer->logEvent('URL', 'URL.Method', 0, 'GET /api/user/323142/login');

// Transaction应该叫Call
$producer->startTransaction('Call', 'https://www.baidu.com/login');   // 访问了哪个服务的哪个接口
sleep(1);   // 模拟Call花费了1秒，造成一个Long-call
$producer->logEvent('Call.Remote', 'https://www.baidu.com/login', 0, ['uid' => 323142]);
$producer->endTransaction();

$producer->endTransaction();