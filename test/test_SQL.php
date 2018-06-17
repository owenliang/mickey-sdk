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
$producer->startTransaction('URL', '/api/user/{}/messages');    // /api/user/32124/messages中的数字323142需要打码成{}

$producer->startTransaction('SQL', "SELECT userdb");
$producer->logEvent('SQL.Method', 'SELECT');
$producer->logEvent('SQL.Database', 'jdbc:mysql://userdb_mysql_m01:3306/userdb');

sleep(1);   // 模拟SQL花费了1秒，造成一个Long-sql
$producer->endTransaction(0, ['sql' => "SELECT * FROM messages where uid=?"]);

$producer->endTransaction();