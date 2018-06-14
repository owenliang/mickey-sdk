<?php
namespace cat;

// 分布式RPC的跨节点上下文 + 本节点的基础信息
class Context
{
    public $catRootMessageId;
    public $catParentMessageId;
    public $catChildMessageId;

    public $domain;
    public $hostname;
    public $ip;
}