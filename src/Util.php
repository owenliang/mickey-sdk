<?php
namespace cat;

class Util
{
    public static function getHostname()
    {
        return gethostname();
    }

    public static function getLocalIp()
    {
        if (!empty($_SERVER['SERVER_ADDR'])) {
            return $_SERVER['SERVER_ADDR'];
        }
        return "127.0.0.1";
    }
}