<?php
namespace cat;

// 分布式RPC的跨节点上下文 + 本节点的基础信息
class Context
{
    public $catRootMessageId = '';  // 暂时没有用到，CAT目前只能构建子调用链，无法构建完整调用链
    public $catParentMessageId = '';
    public $catChildMessageId = '';

    public $domain;
    public $hostname;
    public $ip;
}