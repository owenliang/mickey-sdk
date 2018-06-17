<?php

require_once __DIR__ . '/../vendor/autoload.php';

/**
 *  发起调用的CLIENT
 */

$serverContext = new \cat\Context();
$serverContext->domain = 'mickey.smzdm.com';
$serverContext->hostname = \cat\Util::getHostname();
$serverContext->ip = \cat\Util::getLocalIp();

$clientContext = (function($context)
{
// CAT核心
    $manager = new \cat\Manager(['routerApi' => 'http://mickey.smzdm.com:8080/cat/s/router']);
    $manager->setServerContext($context);

// 调用链第一环, 没有parent span
    $context->catChildMessageId = $manager->generateMessageId();

// CAT消息构造
    $producer = new \cat\Producer($manager);

// 启动最外层服务端transaction
    $producer->startTransaction('URL', '/api/user/{}/login');    // /api/user/32124/login中的数字323142需要打码成{}

    $producer->logEvent('URL', 'URL.Server', 0, 'http://mickey.smzdm.com:8080');
    $producer->logEvent('URL', 'URL.Method', 0, 'GET /api/user/323142/login');

// Transaction应该叫Call
    $producer->startTransaction('Call', 'https://www.baidu.com/login');   // 访问了哪个服务的哪个接口
    $clientContext = $manager->getClientContext();

    // 这个字段必须设置：记录Client side span
    $producer->logEvent('RemoteCall', '', 0, $clientContext->catChildMessageId);

    $producer->logEvent('Call.Remote', 'https://www.baidu.com/login', 0, ['uid' => 323142]);
    $producer->endTransaction();

    $producer->endTransaction();

    return $clientContext;
})($serverContext);

/*
 * clientContext从客户端的RPC发往服务端
 *
 * */

/**
 *  被调用的SERVER
 */

$clientContext->domain = 'www.baidu.com';
$clientContext->hostname = \cat\Util::getHostname();
$clientContext->ip = \cat\Util::getLocalIp();

(function($context)
{
// CAT核心
    $manager = new \cat\Manager(['routerApi' => 'http://mickey.smzdm.com:8080/cat/s/router']);
    $manager->setServerContext($context);

// CAT消息构造
    $producer = new \cat\Producer($manager);

// 启动最外层服务端transaction
    $producer->startTransaction('URL', '/login');

    $producer->logEvent('URL', 'URL.Server', 0, 'http://www.baidu.com');
    $producer->logEvent('URL', 'URL.Method', 0, 'GET /login');

    $producer->endTransaction();
})($clientContext);