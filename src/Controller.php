<?php

namespace MagicCube;

use Pkg\Glob;
use MagicCube\Uranus\Planet;

class Controller
{
    const VERSION = '22.5.12';
    /*
    参数
    */
    // 模板
    public static $templateDir = null;
    public static $srcDir = null;
    public static $vars = [];
    public static $errorPages = array(
        404 => array(
            null,
            'error/404',
            array(),
        ),
    );
    public static $script = null;
    public static $data = null;
    // 响应
    public static $headers = array();
    public static $body = null;
    // 模块
    public static $default = array(
        'module' => 'index',
        'controller' => 'Index',
        'action' => 'index',
        'param' => array(),
        'offset' => 2,
    );

    /*
    变量、配置
    */
    public static $_request = array();
    public static $config = array();

    /*
    开关
    */
    protected $enableView = true;
    public $moduleConfig = true;

    // 初始化
    public function __construct($vars = [])
    {
        $uriInfo = $vars['uriInfo'];
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

        // 22.5.12
        $uriInfo =& static::$vars['uriInfo'];
        $result_values = Planet::actionIsNumeric($uriInfo['action']);
        if (true === $result_values) {
            $uriInfo['param'] = array($uriInfo['action']);
            $uriInfo['act'] = '_numeric';
        }
    }

    // 通过析构函数来执行动作
    public function __destruct0()
    {
        $uriInfo =& static::$vars['uriInfo'];

        // 调用动作方法
        $methods = get_class_methods($this);
        $actionName = ($uriInfo['act'] ?? null) ?: $uriInfo['action'];
        // 标准名称转换
        $str = preg_replace("/[\-_]+/", ' ', $actionName);
        $uc = ucwords($str);
        $lc = lcfirst($uc);
        $actionName = $ac = preg_replace("/\s+/", '', $lc);
        #print_r([$actionName, $str, $uc, $lc, $ac, __FILE__, __LINE__]);
        $actionable = static::$vars['actionable'] ?? null;
        if (!in_array($actionName, $methods)) {
            if ($actionable) {
                $actionName = static::$vars['actionable'] ?? 'index';
            }

        }
        $var = call_user_func_array(array($this, $actionName), $uriInfo['param']);

        // 输出
        if (true === $this->enableView) {
            static::_render($uriInfo, $var);
        } else {
            print_r($var);
        }
    }

    public function __destruct()
    {
        $actionable = static::$vars['actionable'] ?? null;
        $uriInfo =& static::$vars['uriInfo'];
        $result_values = Planet::isFixedAction($uriInfo);
        $methods = get_class_methods($this);

        $actionName = $uriInfo['action'];
        if (true === $result_values) {
            $actionName = $uriInfo['act'];
        }
        if (!in_array($actionName, $methods)) {
            if ($actionable) {
                $actionName = static::$vars['actionable'] ?? 'index';
            }
        }

        $var = call_user_func_array(array($this, $actionName), $uriInfo['param']);
        if (true === $this->enableView) {
            static::_render($uriInfo, $var);
        } else {
            print_r($var);
        }
    }

    // 缺省动作 - 未找到页面
    public function __call($name, $arguments)
    {
        $request_uri = $_SERVER['REQUEST_URI'] ?? null;
        $version = self::VERSION;
        // 未安装模块
        if (static::$default === self::$vars['uriInfo']) {
            static::$templateDir = dirname(__DIR__) ."/app/index/template";

        } elseif (static::$errorPages[404] ?? null) { // 404 自定义
            static::_header(404, 'Not Found');
            list($templateDir, $script, $var) = static::$errorPages[404];
            static::$templateDir = $templateDir ?: dirname(__DIR__) ."/app/index/template";
            static::$script = $script;
            static::$data = $var ?: get_defined_vars();

        } else { // 404 无定制页面
            $this->enableView = false;
        }

        $init = self::$vars;
        $file = __FILE__;
        $line = __LINE__;
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
    public static function _redirect($url = null, $formData = null)
    {
        $sent = headers_sent();
        if (true === $sent) {
            print_r([__FILE__, __LINE__, get_defined_vars()]);
            exit;
        }
        if (null !== $formData) {
            return static::_post($url, $formData);
        }
        header("Location: $url");
        exit;
    }

    // 使用模板引擎渲染
    public static function _render($uriInfo = array(), $var = array(), $return = null)
    {
        global $template;
        $script = strtolower($uriInfo['controller']) .'/'. $uriInfo['action'];
        // 计划：使用模板（类似调度器类里控制器类名模板）
        $theme = $uriInfo['theme'] ?? null;
        $srcDir = static::$srcDir ? '/'. static::$srcDir : null;
        $themeDir = $theme ? "/theme/$theme" : null;
        $templateDir = static::$templateDir ?: ROOT .'/app/'. strtolower($uriInfo['module']). $srcDir  . $themeDir . '/template';
        $template->setTemplateDir($templateDir);
        $what = $template->render(static::$script ?: $script, static::$data ?: $var);
        // 带头信息的输出
        if (null === $return) {
            static::_response($what);
        } elseif (true === $return) { // 返回结果
            return $what;
        } else { // 直接输出
            print_r($what);
        }
    }

    // 获取模块配置项
    public static function _config($item = null, $value = null)
    {
        return Glob::conf($item, $value, static::$config);
    }

    // 添加头信息
    public static function _header($key = null, $value = null, $replace = null, $http_response_code = null)
    {
        if (is_numeric($key)) {
            $value = "HTTP/1.1 $key $value";
        }
        static::$headers[$key] = array($value, $replace, $http_response_code);
    }

    // 响应
    public static function _response($body = null, $header = null)
    {
        $variable = $header ?: static::$headers;
        // 是否已经发送
        if ($variable && headers_sent($file, $line)) {
            static::_dump_header(get_defined_vars());
        }
        // 遍历
        foreach ($variable as $key => $value) {
            list($srting, $replace, $http_response_code) = $value;
            header($srting, $replace, $http_response_code);
        }
        // 输出
        print_r($body ?: static::$body);
    }

    // 所有头信息
    public static function _dump_header()
    {
        print_r(
            array(
                'args' => func_get_args(),
                'code' => http_response_code(),
                'list' => headers_list(),
            )
        );
        exit;
    }

    //
    public static function _post($url = null, $formData = null)
    {
        static::$script = 'index\post';
        return get_defined_vars();
    }
}
