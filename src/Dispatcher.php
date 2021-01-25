<?php

namespace MagicCube;

class Dispatcher
{
    public static $uri = null;

    public function __construct($uri = null)
    {
        $variable = get_defined_vars();
        foreach ($variable as $key => $value) {
            self::$$key = $value;
        }
    }

    public static function dispatch($return = null)
    {
        $ns = "app\{m}\controller\{c}";
        $arr = array();
        $arr[] = $uri = self::$uri;
        $arr[] = $uriInfo = self::parseUri($uri);
        extract($uriInfo);

        // 大小写标准化
        $c = preg_replace("/[\-]+/", ' ', $controller);
        $subject = ucwords($c);
        $controller = preg_replace("/\s+/", '', $subject);

        // 检测类
        $controller = is_numeric($controller) ? 'Index' : $controller;
        $arr[] = $class_name = str_replace(["{m}", "{c}"], [$module, $controller], $ns);
        $exists = class_exists($class_name);
        $offset = 0;
        if (!$exists) {
            $offset++;
            $uriInfo['act'] = lcfirst($controller);
            $arr[] = $class_name = str_replace(["{m}", "{c}"], [$module, "Index"], $ns);
            $exists = class_exists($class_name);
            if (!$exists) {
                $offset++;
                $arr[] = $class_name = str_replace(["{m}", "{c}"], ["index", ucfirst($module)], $ns);
                $exists = class_exists($class_name);
                if (!$exists) {
                    $offset++;
                    $uriInfo['act'] = $module;
                    $arr[] = $class_name = str_replace(["{m}", "{c}"], ["index", "Index"], $ns);
                    $exists = class_exists($class_name);
                    if (!$exists) {
                        $offset++;
                        $arr[] = $class_name = "MagicCube\Controller";
                    }
                }
            }
        }
        $uriInfo['offset'] = $offset;
        // 返回解析结果
        if ('return' === $return) {
            return get_defined_vars();
        }

        // 对象
        $vars = array('uriInfo' => $uriInfo);
        $obj = new $class_name($vars);
        // 正常输出并返回所有结果
        if ($return) {
            return get_defined_vars();
        }
        // 仅返回对象
        return $obj;
    }

    public static function parseUri($str = null)
    {
        $m = 'index';
        $c = 'Index';
        $a = 'index';
        $string = ltrim($str, '/');
        $array = explode('/', $string);
        $module = array_shift($array) ?: $m;
        $controller = $array ? array_shift($array) : $c;
        $action = $array ? array_shift($array) : $a;
        $param = $array;
        return array(
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'param' => $param,
        );
    }
}
