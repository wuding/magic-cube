<?php

namespace MagicCube;

class Controller
{
    public $uriInfo = array();
    public $methods = array();
    public $enableView = true;

    public function __construct($vars = [])
    {
        $this->setVars($vars);
        $this->methods = get_class_methods($this);
    }

    public function __destruct()
    {
        global $template;
        $uriInfo = $this->uriInfo;
        $actionInfo = isset($uriInfo['action']) ? $uriInfo['action'] : null;
        $action = in_array($actionInfo, $this->methods) ? $actionInfo : '_action';
        # print_r(get_defined_vars());

        $var = $this->$action();
        $var = $var ? : ['__nothing__' => null];
        # print_r($var);
        $uriInfoRun = $this->uriInfo;
        if ($action != $uriInfoRun['action']) {
            $action = $uriInfoRun['action'];
        }

        if ($this->enableView) {
            $template->setTemplateDir(ROOT . '/app/' . strtolower($uriInfo['module']) . '/template');
            $controller = lcfirst($uriInfo['controller']);
            $script = "$controller/$action";
            echo $template->render($script, $var);
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
}

