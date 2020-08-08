<?php

namespace MagicCube;

use Ext\File;
use Ext\Zlib;
use Ext\X\PhpRedis;
use model\Glob;

class Controller
{
    use \MagicCube\Traits\_Abstract;

    public $uriInfo = array('module' => '_', 'controller' => '_', 'action' => '_');
    public $methods = array();
    public $enableView = true;
    public static $enableCache = null;
    public $outputCallback = null;
    public $debugRender = false;
    public $viewTag = 'pre';
    public $viewStyle = ' style="width:100%; height:100%; margin: 0;"';
    public $htmlSpecialChars = false;
    # public $htmlTag = '</textarea>';
    public $namespace = "app\{m}\controller\{c}";
    public static $hook = null;
    public $templateDir = null;
    public static $varname = array(
        'enableCache',
        'cacheTTL',
    );
    public static $cacheTTL = 10;

    public function __construct($vars = [])
    {

        //时间节点
        Glob::$timeNode['REQUEST_TIME_FLOAT'] = $_SERVER['REQUEST_TIME_FLOAT'];
        Glob::time('MAGIC_CUBE');
        Glob::diff(__METHOD__);
        // 设置属性
        $this->params = $vars['routeInfo'][2];
        $this->_setVars($vars);
        $this->methods = get_class_methods($this);

    }

    public function __destruct()
    {

        global $template;
        # $this->htmlTag = "</$this->viewTag>";
        Glob::diff(__METHOD__);
        extract($this->_info());
        // 执行动作，并导入可能修改后的信息变量
        $var = $this->$action();
        Glob::diff($script);
        $output = '';

        // 渲染模板
        if (true === $this->enableView) {
            if (null !== $this->outputCallback) {
                $template->setCallback($this->outputCallback);
            }
            $template->setTemplateDir($templateDir);

            // 缓存
            $uri = $_SERVER['REQUEST_URI'] ?? '<null>';
            #$cacheKey = "uri_$uri";
            extract(self::_cacheInfo($templateDir, $script, $var, $uri, 'uri'));
            extract(self::_render($cacheKey, $script, $var, $cacheFile));
            Glob::diff('TEMPLATE_RENDER');

            // 处理
            if (null !== self::$hook) {
                call_user_func_array(self::$hook, [get_defined_vars()]);
                goto __END__;
            }
            $type = gettype($render);
            if ('NULL' !== $type) {
                $output = print_r($render, true);
            }
            if ('NULL' === $type && !$this->outputCallback || $this->debugRender) {
                $output = print_r(['render_type' => $type, 'file' => __FILE__, 'line' => __LINE__, 'render_result' => $render], true);
            }
        } else {
            switch ($this->enableView) {
                case 0:
                    $output = print_r($var, true);
                    $this->viewTag = null;
                    break;

                case 1:
                    $output = print_r($var, true);
                    break;

                case 2:
                    var_dump($var);
                    break;

                case 3:
                    $output = var_export($var, true);
                    break;

                case 4:
                    $output = gettype($var);
                    break;

                default:
                    var_dump($this->enableView);
                    break;
            }
            $output = $this->_debugView($output);
        }

        __END__:
        // 控制台
        $console = $_COOKIE['stat'] ?? null;
        if ($console) {
            $log = Glob::$timeNode;
            $req = $_SERVER['REQUEST_TIME_FLOAT'];
            $now = microtime(true);
            $log['DATETIME'] = date('Y-m-d H:i:s');
            $log['REQUEST_DATETIME'] = date('Y-m-d H:i:s', $_SERVER['REQUEST_TIME']);
            $log['REQUEST_TIME_FLOAT'] = $req;
            $log['NOW'] = $now;
            $log['DIFF'] = $now - $req;
            $log['DIFF_MS'] = Glob::_round($log['DIFF'] * 1000, 2);
            asort($log);
            $output .= '<pre style="clear:left">'. print_r($log, true) .'</pre>';
        }
        // 压缩
        if ('on' !== Glob::conf('gzip')) {
            exit($output);
        }
        $encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? null;
        $arr = preg_split('/,\s+/', $encoding);
        if (in_array('gzip', $arr)) {
            header("Content-Encoding: gzip");
            $output = Zlib::encode($output);
        }
        echo $output;
    }

    public function __call($name, $arguments)
    {
        extract($this->uriInfo);
        $className = $this->_replaceNamespace($module, $controller);
        // className 应该输出所有检测过的类名
        $this->uriInfo['controller'] = '_error';
        $this->uriInfo['action'] = '404';
        return array(
            '__controller__' => array(
                'code' => 404,
                'msg' => array(
                    '' => "Not Found $className::$action()",
                    'file' => __FILE__,
                    'line' => __LINE__,
                    'method' => __METHOD__,
                ),
                'data' => array(
                    'obj' => $this,
                ),
            ),
        );
    }

    // 模块模板信息
    public function _info()
    {

        $uriInfo = $this->uriInfo;
        extract($uriInfo);
        $actionInfo = isset($uriInfo['action']) ? $uriInfo['action'] : null;
        $actionInfo = is_numeric($actionInfo) ? '_numeric' : $actionInfo;
        $action = in_array($actionInfo, $this->methods) ? $actionInfo : '_action';
        // 应该传递变量，让模板替换规则获取目录和文件名
        $templateDir = $this->templateDir ?: ROOT . '/app/' . strtolower($module) . '/template';
        $controller = strtolower($controller);
        $script = "$controller/$action";
        return get_defined_vars();

    }

    // 缓存文件键名
    public static function _cacheInfo($templateDir, $script, $var, $uri = null, $prefix = 'cache')
    {

        $cacheFile = self::_cacheFilename($templateDir, $script, $var, $uri);
        $cacheMd5 = md5($cacheFile);
        $cacheKey = $prefix .'_'. $cacheMd5;
        return get_defined_vars();

    }

    // 缓存
    public static function _cacheFilename($templateDir, $script, $var, $uri = null)
    {

        $var = null === $uri ? $var : $uri;
        $dir = md5($templateDir);
        $json = json_encode($var);
        $hash = md5($json);
        $script_file = $templateDir .'/'. $script .'.php';
        #$md5 = md5_file($script_file);
        return $cacheFile = ROOT ."/tmp/cache/template/$dir/$script/$hash.html";

    }


    // 模板输出
    public static function _render($cacheKey, $script, $var, $cacheFile)
    {

        global $template;
        $render = static::$enableCache ? PhpRedis::get($cacheKey) : false;
        $ttl = self::_(1);
        if (false === $render || 0 > $ttl) {
            // 调用模板静态方法
            $render = $template->render($script, $var);#var_dump($render);
            #File::putContents('controler.txt', print_r(get_defined_vars(), true));
            #File::putContents($cacheFile, $render);
            if (static::$enableCache) {
                if (-1 < $ttl) {
                    PhpRedis::set($cacheKey, $render, $ttl ?: []);
                } else {
                    $del = PhpRedis::del($cacheKey);
                    return array(
                        'render' => array(
                            'len' => strlen($render),
                            'del' => $del,
                        ),
                    );
                }
            }
        }
        return get_defined_vars();

    }

    // 静态属性读写
    public static function _($varname, $value = null, $set = null)
    {

        if (is_numeric($varname)) {
            $varname = static::$varname[$varname];
        }
        $val = static::$$varname;
        if (null !== $value || $set) {
            static::$$varname = $value;
        }
        return $val;

    }
}
