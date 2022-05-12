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
    const VERSION = '22.5.12';

    public static function actionIsNumeric($action = null)
    {
        return is_numeric($action);

    }

    public static function isFixedAction($uriInfo = array())
    {
        return isset($uriInfo['act']);
    }
}
