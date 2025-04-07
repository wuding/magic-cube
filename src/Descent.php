<?php

namespace Frost;

use function Func\{post, request, server};

class Descent extends _Abstract
{
    const VERSION = 25.0204;
    const REVISION = 1;

    static $properties = [
    ];

    var $property_hooks = [
        'get' => [
        ],
        'set' => [
        ],
    ];

    function __construct()
    {
        // print_r([__LINE__, __METHOD__,  __FUNCTION__]);
        $this->init();
    }

    function __destruct()
    {
        // print_r([__LINE__,__METHOD__,__FUNCTION__]);
    }

    function __get($name)
    {
        if (array_key_exists($name, self::$properties)) {
            return self::$properties[$name];
        }
        return null;
    }

    function __set($name, $value)
    {
        self::$properties[$name] = $value;
    }

    function init()
    {
        // print_r([__LINE__,__METHOD__,__FUNCTION__]);
        $server = $_SERVER;
        ksort($server);
        $this->server = $server;
        // print_r($server);
    }

    function run($uri = null)
    {
        $subject = $uri;
        $c = "app\search\conf\Dev";
        $o = new $c;

// $script = 'index/index';
$action = 'index';
$pattern = "#^/share/([0-9]+)#";
        if (preg_match($pattern, $subject, $matches)) {
// print_r($matches);die;
$action = 'share';
// $script = 'index/share';
            list($varname, $id) = $matches;
        }

        $class = "app\search\src\controller\Index";
        $obj = new $class;
        $function = [$obj, $action];
        $param_arr = [[$id], $this->server['QUERY_STRING'] ?? null];
        return $res = call_user_func_array($function, $param_arr);#
        print_r($res);
    }
}
