<?php

namespace MagicCube;

class Controller
{
    use \MagicCube\Traits\_Abstract;

    public $uriInfo = array('module' => '_', 'controller' => '_', 'action' => '_');
    public $methods = array();
    public $enableView = true;
    public $outputCallback = null;
    public $debugRender = false;
    public $viewTag = 'pre';
    public $viewStyle = ' style="width:100%; height:100%; margin: 0;"';
    public $htmlSpecialChars = false;
    # public $htmlTag = '</textarea>';
    public $namespace = "app\{m}\controller\{c}";

    public function __construct($vars = [])
    {
        $this->_setVars($vars);
        $this->params = $this->routeInfo[2];
        $this->methods = get_class_methods($this);
    }

    public function __destruct()
    {
        global $template;
        # $this->htmlTag = "</$this->viewTag>";
        $uriInfo = $this->uriInfo;
        $actionInfo = isset($uriInfo['action']) ? $uriInfo['action'] : null;
        $actionInfo = is_numeric($actionInfo) ? '_numeric' : $actionInfo;
        $action = in_array($actionInfo, $this->methods) ? $actionInfo : '_action';

        // 执行动作，并导入可能修改后的信息变量
        $var = $this->$action();
        extract($this->uriInfo);

        if (true === $this->enableView) {
            if (null !== $this->outputCallback) {
                $template->setCallback($this->outputCallback);
            }
            // 应该传递变量，让模板替换规则获取目录和文件名
            $template->setTemplateDir(ROOT . '/app/' . strtolower($module) . '/template');
            $controller = strtolower($controller);
            $script = "$controller/$action";
            $render = $template->render($script, $var);
            $type = gettype($render);
            if ('NULL' != $type) {
                print_r($render);
            }
            if ('NULL' == $type && !$this->outputCallback || $this->debugRender) {
                print_r(['render_type' => $type, 'file' => __FILE__, 'line' => __LINE__, 'render_result' => $render]);
            }
        } else {
            $output = null;
            switch ($this->enableView) {
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
                    # code...
                    break;
            }
            echo $this->viewTag ? "<$this->viewTag$this->viewStyle>" : '';
            echo PHP_EOL;
            echo $this->htmlSpecialChars ? htmlspecialchars($output) : $output;
            echo PHP_EOL;
            echo $this->viewTag ? "</$this->viewTag>" : '';
        }
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
}
