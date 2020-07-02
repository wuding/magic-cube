<?php

namespace MagicCube;

class Controller
{
    public $uriInfo = array();
    public $methods = array();
    public $enableView = true;
    public $outputCallback = null;
    public $debugRender = false;
    public $viewTag = 'pre';
    public $viewStyle = ' style="width:100%; height:100%; margin: 0;"';
    public $htmlSpecialChars = false;
    # public $htmlTag = '</textarea>';

    public function __construct($vars = [])
    {
        $this->setVars($vars);
        $this->methods = get_class_methods($this);
    }

    public function __destruct()
    {
        global $template;
        # $this->htmlTag = "</$this->viewTag>";
        $uriInfo = $this->uriInfo;
        $actionInfo = isset($uriInfo['action']) ? $uriInfo['action'] : null;
        $action = in_array($actionInfo, $this->methods) ? $actionInfo : '_action';

        $var = $this->$action();
        $uriInfoRun = $this->uriInfo;
        if ($action != $uriInfoRun['action']) {
            $action = $uriInfoRun['action'];
        }

        if (true === $this->enableView) {
            if (null !== $this->outputCallback) {
                $template->setCallback($this->outputCallback);
            }
            $template->setTemplateDir(ROOT . '/app/' . strtolower($uriInfo['module']) . '/template');
            $controller = strtolower($uriInfo['controller']);
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
        return [$name, $arguments, __FILE__, __LINE__];
    }

    public function setVars($vars = [])
    {
        foreach ($vars as $key => $value) {
            $this->$key = $value;
        }
    }

    public function _action()
    {
        $this->uriInfo['action'] = '_action';
        return array(
            '__controller__' => array(
                'code' => 404,
                'msg' => array(
                    '' => '404 Not Found',
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
