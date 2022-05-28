<?php

/**
 * URL
 * Request & Response
 * Anchor | hyperlink
 * Network & Upload & Save
 */

namespace MagicCube\Uranus;

class Planet
{
    const VERSION = '22.5.28';

    public static function actionIsNumeric($action = null)
    {
        return is_numeric($action);

    }

    public static function isFixedAction($uriInfo = array())
    {
        return isset($uriInfo['act']);
    }

    // 标准名称转换
    public static function fixActionName($actionName)
    {
        $str = preg_replace("/[\-_]+/", ' ', $actionName);
        $uc = ucwords($str);
        $lc = lcfirst($uc);
        $ac = preg_replace("/\s+/", '', $lc);
        return $ac;
    }
}
