<?php

namespace MagicCube;

class Dispatcher
{
    const VERSION = '21.2.9';
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
        //=s
        $ns = "app\{m}\controller\{c}";

        //=f
        $classes = array();

        //=z
        $uri = self::$uri;
        $uriInfo = self::parseUri($uri);
        extract($uriInfo);

        // 大小写标准化
        $c = preg_replace("/[\-]+/", ' ', $controller);
        $subject = ucwords($c);
        $controller = preg_replace("/\s+/", '', $subject);

        //=sh
        // 检测类
        $module = is_numeric($module) ? 'index' : lcfirst($module);
        $controller = is_numeric($controller) ? 'Index' : ucfirst($controller);
        $class_map = array(
            array($module, $controller, null),
            array($module, 'Index', lcfirst($controller)),
            array('index', ucfirst($module), lcfirst($controller)),
            array('index', 'Index', $module),
        );

        // 遍历
        $offset = 0;
        $first = null;
        foreach ($class_map as $try) {
            $act = array_pop($try);
            // 重名
            if ($first === $try) {
                continue 1;
            }
            $classes[] = $class_name = str_replace(["{m}", "{c}"], $try, $ns);
            // 类检测
            $exists = class_exists($class_name);
            if ($exists) {
                if ($act) {
                    $uriInfo['act'] = $act;
                }
                $uriInfo['class'] = $class_name;
                break 1;
            }
            $offset++;
            $first = $try;
        }

        //=l
        // 缺省类
        if (!$exists) {
            $offset++;
            $classes[] = $class_name = "MagicCube\Controller";
        }
        $uriInfo['offset'] = $offset;

        //=j
        // 返回解析结果
        if ('return' === $return) {
            return get_defined_vars();
        }

        //=g
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
