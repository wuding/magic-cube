<?php

namespace MagicCube;

class Controller
{
    public static $templateDir = null;
    public static $vars = [];
    public static $_request = array();

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
        );
        foreach ($req as $key => $value) {
            if (array_key_exists($value, $_SERVER)) {
                static::$_request[$key] = $_SERVER[$value];
            }
        }
    }

    public function __destruct()
    {
        global $template;
        $uriInfo =& static::$vars['uriInfo'];
        // 调用动作方法
        $var = call_user_func_array(array($this, ($uriInfo['act'] ?? null) ?: $uriInfo['action']), $uriInfo['param']);

        // 使用模板引擎渲染
        $script = strtolower($uriInfo['controller']) .'/'. $uriInfo['action'];
        $templateDir = self::$templateDir ?: ROOT .'/app/'. strtolower($uriInfo['module']) . '/template';
        $template->setTemplateDir($templateDir);
        $what = $template->render($script, $var);
        print_r($what);
    }

    public function __call($name, $arguments)
    {
        print_r(get_defined_vars());
        print_r([__FILE__, __LINE__]);
    }

    /**
     * 保留方法
     */

    public static function _request($key = null, $value = null)
    {
        if (array_key_exists($key, static::$_request)) {
            return static::$_request[$key];
        }
        return $value;
    }

    public static function _redirect($url = null)
    {
        header("Location: $url");
        exit;
    }
}
