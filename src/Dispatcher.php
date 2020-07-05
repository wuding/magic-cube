<?php

namespace MagicCube;

class Dispatcher
{
    use \MagicCube\Traits\_Abstract;

    public $routeInfo = array();
    public $httpMethod = null;
    public $modulesEnable = null;
    public $uri = null;
    public $namespace = "app\{m}\controller\{c}";
    public $alias = [];

    public function __construct($routeInfo = [], $httpMethod = null)
    {
        $vars = get_defined_vars();
        $this->_setVars($vars);

        global $_VAR;
        $this->uri = $_VAR['uri'];
    }

    public function dispatch($return = null)
    {
        $routeResult = $this->routeInfo();
        extract($routeResult);
        # $handler = 'test';

        $handler = $handler ? : $this->uri;
        if (is_numeric($handler)) {
            // 不要直接退出
            return array($handler, __FILE__, __LINE__);

        } elseif (is_array($handler)) {
            // 每种类型单独匹配
            $uri = $handler[0];
            $include =  include $handler[1];
            if (is_numeric($include)) {
                return $include;
            }
            $handler = array_shift($handler);
        }

        if (preg_match('/\//', $handler)) {
            $uriInfo = $this->parseUri($handler);

            /*
            extract($uriInfo);
            $handler_encode = $this->encodeHandler($method, $module, $controller, $action, $param);
            */
        } else {
            $uriInfo = $this->parseHandler($handler);
        }

        // 别名，路由干啥呢
        $variable = $this->alias;
        foreach ($variable as $key => $value) {
            $pattern = "/^($value)$/i";
            if (preg_match($pattern, $uriInfo['module'])) {
                $uriInfo['module'] = $key;
                break;
            }
        }

        $fix = $fixed = $this->fixedUriInfo($uriInfo);
        $cls = $this->checkClassName($fixed);
        $className = trim($cls, '\\');
        $classes = explode('\\', $className);
        $ctrl = array_pop($classes);
        if (strtolower($fix['module']) != strtolower($classes[1])) {
            $fix['controller'] = $fix['module'];
            $fix['module'] = $classes[1];
        }
        if (strtolower($fix['controller']) != strtolower($ctrl)) {
            $fix['action'] = strtolower($fix['controller']);
            $fix['controller'] = $ctrl;
        }

        if ($return) {
            return get_defined_vars();
        }
        return $object = $this->run($cls, $fix);
    }

    /**
     * 解析路由信息数据
     *
     * @param      <type>  $routeInfo  The route information
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function routeInfo($routeInfo = null)
    {
        $routeInfo = $routeInfo ? : $this->routeInfo;
        $status = isset($routeInfo[0]) ? $routeInfo[0] : -1;
        $handler = null;
        $vars = null;
        $allowedMethods = null;

        switch ($status) {
            case 0:
                $handler = 'all:_module!_controller@_action';
                break;
            case 1:
                $handler = isset($routeInfo[1]) ? $routeInfo[1] : $handler;
                $vars = isset($routeInfo[2]) ? $routeInfo[2] : $vars;
                break;
            case 2:
                $handler = '_error/route';
                $allowedMethods = isset($routeInfo[1]) ? $routeInfo[1] : $allowedMethods;
                break;
            default:
                $handler = '_error/route';
                break;
        }

        return $result = get_defined_vars();
    }

    /**
     * 解析路由句柄字符串
     *
     * @param      <type>  $str    The string
     * @param      string  $type   The type
     *
     * @return     array   ( description_of_the_return_value )
     */
    public function parseHandler($str, $type = null)
    {
        $method = 'all';
        $module = $type ? 'index' : '_module';
        $controller = '_Controller';
        $action = '_action';
        $param = null;

        $methodInfo = explode(':', $str);
        $module_str = $methodInfo[0];
        if (1 < count($methodInfo)) {
            list($method_var, $module_str) = $methodInfo;
            $method = $method_var ? : $method;
        }

        $moduleInfo = explode('!', $module_str);
        $controller_str = $moduleInfo[0];
        if (1 < count($moduleInfo)) {
            list($module_var, $controller_str) = $moduleInfo;
            $module = $module_var ? : $module;
        }

        $controllerInfo = explode('@', $controller_str);
        $action_str = $controllerInfo[0];
        if (1 < count($controllerInfo)) {
            list($controller_var, $action_str) = $controllerInfo;
            $controller = $controller_var ? : $controller;
        }

        $actionInfo = explode('$', $action_str);
        $action = $actionInfo[0] ? : $action;
        if (1 < count($actionInfo)) {
            list($action_var, $param_str) = $actionInfo;
            $action = $action_var ? : $action;
            $param = urldecode($param_str);
        }

        return array(
            'method' => $method,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'param' => $param ,
        );
    }

    /**
     * 编码路由句柄字符串标准格式
     *
     * @param      <type>  $method      The method
     * @param      <type>  $module      The module
     * @param      <type>  $controller  The controller
     * @param      <type>  $action      The action
     * @param      <type>  $param       The parameter
     *
     * @return     <type>  ( description_of_the_return_value )
     */
    public function encodeHandler($method = null, $module = null, $controller = null, $action = null, $param = null)
    {
        $method = $method ? : 'all';
        $module = $module ? : '_module';
        $controller = $controller ? : '_Controller';
        $action = $action ? : '_action';
        $param = $param ? : '';

        $str = $method;
        if ($module) {
            $str .= ":$module";
        }

        if ($controller) {
            $str .= "!$controller";
        }

        if ($action) {
            $str .= "@$action";
        }

        if ($param) {
            $param = urldecode($param);
            $param = urlencode($param);
            $str .= "\$$param";
        }
        return $str;
    }

    /**
     * 解析请求路径
     *
     * @param      <type>   $str    The string
     * @param      integer  $type   The type
     *
     * @return     array    ( description_of_the_return_value )
     */
    public function parseUri($str, $type = null)
    {
        $type = $type ? : $this->modulesEnable;

        $method = null;
        $methodInfo = explode(':', $str);
        $module_str = $methodInfo[0];
        if (1 < count($methodInfo)) {
            list($method_var, $module_str) = $methodInfo;
            $method = $method_var ? : $method;
        }

        $module = 'index';
        $controller = null;
        $action = null;
        $param = null;

        $limit = 3;
        $offset = 0;
        if (is_numeric($type)) {
            $limit = $limit + $type;
        }

        $ltrim = ltrim($module_str, '/');
        $split = explode('/', $ltrim, $limit);
        $count = count($split);

        $first = $split[0];
        $second = $third = $fourth = null;
        if (1 < $count) {
            $second = $split[1];

            if (2 < $count) {
                $third = $split[2];

                if (3 < $count) {
                    $fourth = $split[3];
                }
            }
        }
        $arr = array($first, $second, $third, $fourth);

        if (is_numeric($type)) {
            $offset = $offset + $type;
            $module = $arr[0];
        }
        $controller = $arr[0 + $offset];
        $action = $arr[1 + $offset];
        $param = $arr[2 + $offset];

        return array(
            'method' => $method,
            'module' => $module,
            'controller' => $controller,
            'action' => $action,
            'param' => $param,
        );
    }

    public function fixedUriInfo($vars)
    {
        extract($vars);

        $module = $module ? : (is_numeric($module) ? $module : 'index');
        $controller = $controller ? : (is_numeric($controller) ? $controller : 'Index');
        $action = $action ? : (is_numeric($action) ? $action : 'index');

        return array(
            'method' => $method,
            'module' => $this->fixedName($module),
            'controller' => $this->fixedName($controller),
            'action' => $action,
            'param' => $param,
        );
    }

    public function getUriInfo($uriInfo, $type = null)
    {
        if ('_module' == $uriInfo['module']) {
            if ('_controller' != $uriInfo['controller']) {
                $uriInfo['controller'] = '_Controller';
            }

        } else {
            $uriInfo['module'] = !$type ? $uriInfo['module'] : '_module';
            if ('_controller' != $uriInfo['controller']) {
                $uriInfo['controller'] = '_Controller';
            }
        }
        return $uriInfo;
    }

    public function fixedName($str)
    {
        return $str = ucfirst($str);
    }

    public function getClassName($vars)
    {
        extract($vars);
        return $this->_replaceNamespace($module, $controller);
    }

    public function fiexdClassName($class, $uriInfo, $type = null)
    {
        if (!class_exists($class)) {
            $uriInfo = $this->getUriInfo($uriInfo, $type);
            $class = $this->getClassName($uriInfo);
        }
        return $class;
    }

    public function checkClassName($fixed)
    {
        $cls = $className = $this->getClassName($fixed);
        $class = $this->fiexdClassName($className, $fixed);
        if ($class != $className) {
            $cls = $class;
            $classNm = $this->fiexdClassName($class, $fixed);
            $clsNm = $this->fiexdClassName($classNm, $fixed, 1);
            if ($classNm != $class || $clsNm != $classNm) {
                $cls = $classNm;
                if ($clsNm != $classNm) {
                    $cls = $clsNm;
                }
            }
        }
        return $cls;
    }

    public function run($class, $uriInfo = [])
    {
        if (!class_exists($class)) {
            $class = '\MagicCube\Controller';
        }
        $vars = array(
            'uriInfo' => $uriInfo,
            'routeInfo' => $this->routeInfo,
            'httpMethod' => $this->httpMethod,
            'uri' => $this->uri,
            'namespace' => $this->namespace,
        );
        return new $class($vars);
    }
}
