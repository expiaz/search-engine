<?php

namespace SearchEngine\Core\Misc;

class Logger
{

    public static function log(string $str)
    {
        echo $str;
    }

    public static function logln(string $str)
    {
        self::log($str . (IS_CLI ? "\n" : "<br/>"));
    }

}