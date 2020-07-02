<?php
namespace MagicCube\Traits;

trait _Abstract
{
    public function _setVars($vars = [])
    {
        foreach ($vars as $key => $value) {
            $this->$key = $value;
        }
    }

    public function _replaceNamespace($module, $controller)
    {
        $str = str_replace(['{m}', '{c}'], [$module, $controller], $this->namespace);
        $cls = preg_replace('/\//', '\\', $str);
        return $className = rtrim($cls, '\\');
    }
}
