<?php

namespace MagicCube;

use Ext\Str;

class Dispatcher
{
    const VERSION = 26.0206;
    const EDITION = array(
        10,
        6,
        2,
        1,
        0,
        19,
        'Jfba S',
        '',
        181,
        4574,
    );
    const REVISION = 12;

    public static $uri = null;
    public static $glob = null;
    public static $prefix = null;
    static $srcDir = null;

    public function __construct($uri = null, $glob = null, $prefix = null, $srcDir = null)
    {
        $variable = get_defined_vars();
        foreach ($variable as $key => $value) {
            self::$$key = $value;
        }
    }

    public static function dispatch_v1($return = null, $ns = null, $extra = null, $origin_request_uri = null)
    {
        //=s
        $ns = $ns ?: "app\{m}\controller\{c}";


        //=f
        $classes = array();

        //=z
        $uri = self::$uri;
        $uriInfo = self::parseUri($uri, null, $origin_request_uri);
        extract($uriInfo);

        // 大小写标准化
        $c = preg_replace("/[\-]+/", ' ', $controller);

        $ulArr = self::getUlArr($module, $c);
        extract($ulArr);

        $controller = $controller_ucwords;
        $controller = static::getControllerName($c);

        //=sh
        // 检测类
        $module = is_numeric($module) ? 'index' : $module_lcfirst;
        $controller = is_numeric($controller) ? 'Index' : $controller;

        $theme = self::$glob::conf("module.$module.theme");
        $class_map = array(
            array(null, $module, $controller, null),
            array(null, $module, 'Index', $controller_lcfirst),
            array(null, 'index', $module_ucfirst, $module_lcfirst),
            array(null, 'index', 'Index', $module),
        );



        if ($extra && $theme) {
            $ex = str_replace(["{t}"], array($theme), $extra);
            array_unshift($class_map, array($ex, $module, $controller, null));
        } else {
            $theme = null;
        }

        // 遍历
        $offset = 0;
        $first = null;
        foreach ($class_map as $try) {
            $act = array_pop($try);
            // 重名
            if ($first === $try) {
                continue 1;
            }
            $classes[] = $class_name = str_replace(["{extra}", "{m}", "{c}"], $try, $ns);
            // 类检测
            $exists = class_exists($class_name);
            if ($exists) {
                if ($act) {
                    $uriInfo['act'] = $act;
                }
                $uriInfo['class'] = $class_name;
                if ($theme) {
                    $uriInfo['theme'] = $theme;
                }
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

    static function dispatch($return = null, $ns = null, $extra = null, $origin_request_uri = null)
    {
        $var_array = self::dispatch_vars($return, $ns, $extra, $origin_request_uri);
        extract($var_array);

        //=j
        // 返回解析结果
        if ('return' === $return) {
            return get_defined_vars();
        }

        //=g
        // 对象
        $uriInfo['srcDir'] = static::$srcDir;
        $vars = array('uriInfo' => $uriInfo);
        $obj = new $class_name($vars);
        // 正常输出并返回所有结果
        if ($return) {
            return get_defined_vars();
        }
        // 仅返回对象
        // print_r(get_defined_vars());
        return $obj;
    }

    static function dispatch_vars($return = null, $ns = null, $extra = null, $origin_request_uri = null)
    {
        //=s
        $ns = $ns ?: "app\{m}\controller\{c}";


        //=f
        $classes = array();

        //=z
        $uri = self::$uri;
        $uriInfo = self::parseUri($uri, null, $origin_request_uri);
        extract($uriInfo);

        // 大小写标准化
        $c = preg_replace("/[\-]+/", ' ', $controller);

        $ulArr = self::getUlArr($module, $c);
        extract($ulArr);

        $controller = $controller_ucwords;
        $controller = static::getControllerName($c);

        //=sh
        $class_maps = self::class_map($var_array = [], $uriInfo['module'], $extra, $controller, $ulArr['module_lcfirst'], $ulArr['module_ucfirst'], $ulArr['controller_lcfirst']);
        extract($class_maps);

        // 遍历
        $offset = 0;
        $first = null;
        foreach ($class_map as $try) {
            $act = array_pop($try);
            // 重名
            if ($first === $try) {
                continue 1;
            }
            $classes[] = $class_name = str_replace(["{extra}", "{m}", "{c}"], $try, $ns);
            // 类检测
            $exists = class_exists($class_name);
            if ($exists) {
                if ($act) {
                    $uriInfo['act'] = $act;
                }
                $uriInfo['class'] = $class_name;
                if ($theme) {
                    $uriInfo['theme'] = $theme;
                }
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

        return [
            'uriInfo' => $uriInfo,
            'class_name' => $class_name,
        ];
    }

    public static function parseUri($str = null, $prefix = null, $origin_request_uri = null)
    {
        if (preg_match("/\/unicode/", $str)) {
            $str = $origin_request_uri ?: $str;
        }

        $prefix = $prefix ?: self::$prefix;
        $str = $prefix . $str;
        $m = 'index';
        $c = 'Index';
        $a = 'index';
        $string = ltrim($str, '/');
        $array = explode('/', $string);
        $module = array_shift($array) ?: $m;
        $controller = $array ? array_shift($array) : $c;
        $action = $array ? array_shift($array) : $a;
        $param = $array;
        $return_values = array(
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'param' => $param,
        );
// var_dump($expression = [__FILE__, __LINE__,
// get_defined_vars(),
// ]);exit;
        return $return_values;
    }

    public static function getUlArr($module, $controller_preg_replace)
    {
        $module_lcfirst = Str::lcFirst($module);
        $module_ucfirst = Str::ucFirst($module);

        $controller_ucwords = Str::ucWords($controller_preg_replace);
        $controller_ucfirst = Str::ucFirst($controller_ucwords);
        $controller_lcfirst = Str::lcFirst($controller_ucwords);

        return get_defined_vars();
    }



    //
    public static function getControllerName($controller_preg_replace)
    {
        $controller_ucwords = Str::ucWords(strtolower($controller_preg_replace));
        $controller_name = preg_replace("/[\s]+/", '', $controller_ucwords);



        return $controller_name;
    }

    static function class_map($var_array, $module = null, $extra = null, $controller = null, $module_lcfirst = null, $module_ucfirst = null, $controller_lcfirst = null)
    {
        extract($var_array);

        // 检测类
        $module = is_numeric($module) ? 'index' : $module_lcfirst;
        $controller = is_numeric($controller) ? 'Index' : $controller;

        $theme = self::$glob::conf("module.$module.theme");
        $class_map = array(
            array(null, $module, $controller, null),
            array(null, $module, 'Index', $controller_lcfirst),
            array(null, 'index', $module_ucfirst, $module_lcfirst),
            array(null, 'index', 'Index', $module),
        );

        if ($extra && $theme) {
            $ex = str_replace(["{t}"], array($theme), $extra);
            array_unshift($class_map, array($ex, $module, $controller, null));
        } else {
            $theme = null;
        }

        return [
            'class_map' => $class_map,
            'theme' => $theme,
        ];
    }
}
