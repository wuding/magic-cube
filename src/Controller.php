<?php

namespace MagicCube;

class Controller
{
    public $uriInfo = array();

    public function __construct($vars = [])
    {
        $this->setVars($vars);
    }

    public function __destruct()
    {
        $uriInfo = $this->uriInfo;
        $action = isset($uriInfo['action']) ? $uriInfo['action'] : null;
        $action = $action? : '_action';

        $var = $this->$action();
        $var = $var ? : ['nothing' => null];
        print_r($var);
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

