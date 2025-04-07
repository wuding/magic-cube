<?php

namespace Frost;

class _Abstract
{
    const VERSION = 25.0109;
    const REVISION = 1;

    public $_properties = [];
    public $_property_hooks = [
        'get' => [
            'fullname' => 'get_fullname_hook',
        ],
        'set' => [
            'fullname' => '',
        ],
    ];
    // https://www.php.net/manual/en/migration84.new-features.php

    function __set($name, $value)
    {
        $this->_properties[$name] = $value;
    }

    function __get($name)
    {
        if (array_key_exists($name, $this->_properties)) {
            return $this->_properties[$name];
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }
}
