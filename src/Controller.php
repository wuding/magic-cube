<?php

namespace MagicCube;

use Pkg\Glob;

class Controller
{
    /*
    参数
    */
    public static $templateDir = null;
    public static $vars = [];

    /*
    变量、配置
    */
    public static $_request = array();
    public static $config = array();

    /*
    开关
    */
    public $enableView = true;
    public $moduleConfig = true;

    // 初始化
    public function __construct($vars = [])
    {
        // 导入必须的变量
        foreach ($vars as $key => $value) {
            static::$vars[$key] = $value;
        }

        // HTTP 请求头
        $req = array(
            'method' => 'REQUEST_METHOD',
            'uri' => 'REQUEST_URI',
            'float' => 'REQUEST_TIME_FLOAT',
        );
        foreach ($req as $key => $value) {
            if (array_key_exists($value, $_SERVER)) {
                static::$_request[$key] = $_SERVER[$value];
            }
        }
    }

    // 通过析构函数来执行动作
    public function __destruct()
    {
        $uriInfo =& static::$vars['uriInfo'];

        // 导入模块配置
        if (true === $this->moduleConfig) {
            $class_name = $uriInfo['class'] ?? null;
            if ($class_name) {
                $arr = preg_split("/\\\\/", $class_name);
                $pieces = array_slice($arr, 0, 2);
                array_unshift($pieces, ROOT);
                array_push($pieces, "config.php");
                $file = implode('/', $pieces);
                static::$config = include $file;
            }
        }

        // 调用动作方法
        $var = call_user_func_array(array($this, ($uriInfo['act'] ?? null) ?: $uriInfo['action']), $uriInfo['param']);

        // 输出
        if (true === $this->enableView) {
            static::_render($uriInfo, $var);
        } else {
            print_r($var);
        }
    }

    // 缺省动作 - 未找到页面
    public function __call($name, $arguments)
    {
        $this->enableView = false;
        $info = array(__FILE__, __LINE__);
        return get_defined_vars();
    }

    /**
     * 保留方法
     */

    // 获取 HTTP 请求头信息
    public static function _request($key = null, $value = null)
    {
        if (array_key_exists($key, static::$_request)) {
            return static::$_request[$key];
        }
        return $value;
    }

    // 重定向
    public static function _redirect($url = null)
    {
        $sent = headers_sent();
        if (true === $sent) {
            print_r([__FILE__, __LINE__, get_defined_vars()]);
            exit;
        }
        header("Location: $url");
        exit;
    }

    // 使用模板引擎渲染
    public static function _render($uriInfo = array(), $var = array())
    {
        global $template;
        $script = strtolower($uriInfo['controller']) .'/'. $uriInfo['action'];
        $templateDir = static::$templateDir ?: ROOT .'/app/'. strtolower($uriInfo['module']) . '/template';
        $template->setTemplateDir($templateDir);
        $what = $template->render($script, $var);
        print_r($what);
    }

    // 获取模块配置项
    public static function _config($item = null, $value = null)
    {
        return Glob::conf($item, $value, static::$config);
    }
}
