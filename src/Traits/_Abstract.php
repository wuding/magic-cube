<?php

namespace MagicCube\Traits;

use Ext\Url;
use Ext\Zlib;

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

    public function _expires($seconds = 0)
    {
        if (!$seconds) {
            return false;
        }

        $time = time();
        $modified = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;
        // 协商缓存
        if ($modified) {
            $modify = strtotime($modified);
            $diff = $time - $modify;
            if ($diff < $seconds) {
                $mod = gmdate('D, d M Y H:i:s', $modify);
                header("Last-Modified: $mod GMT");
                header("HTTP/1.1 304");
                exit;
            }
        }

        // 强缓存
        $timestamp = $time + $seconds;
        $exp = gmdate('D, d M Y H:i:s', $timestamp);
        $now = gmdate('D, d M Y H:i:s');
        header("Expires: $exp GMT");
        header("Cache-Control: max-age=$seconds");
        header("Last-Modified: $now GMT");
    }

    // 检测客户端接受确定是否编码
    public static function _gzip($output, $on = null)
    {
        $on = null === $on ? 'on' : strtolower($on);
        if ('on' !== $on) {
            return $output;
        }

        $encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? null;
        $encoding = strtolower($encoding);
        $arr = preg_split('/,\s+/', $encoding);
        if (in_array('gzip', $arr)) {
            header("Content-Encoding: gzip");
            $output = Zlib::encode($output);
        }
        return $output;
    }

    // 链接去掉多余查询键
    public static function _clearUrl($arr = null, $url = null)
    {
        $url = null === $url ? self::_requestUrl() : $url;
        $remove = null === $arr ? ['disabled'] : $arr;
        $URL = parse_url($url);
        parse_str($URL['query'] ?? null, $QUERY);
        foreach ($remove as $key => $value) {
            $val = $QUERY[$key] ?? null;
            if (is_numeric($key)) {
                unset($QUERY[$key]);
            } elseif ($val === $value) {
                unset($QUERY[$key]);
            }
        }

        $query = http_build_query($QUERY);
        $URL['query'] = $query;
        return $link = URL::httpBuildUrl($URL);
        #print_r([$URL, $QUERY, $query, $link]);
    }

    public static function _requestUrl()
    {
        new \Func\Variable;
        return \Func\request_url();
    }

    public function _debugView($output)
    {
        $str = $this->htmlSpecialChars ? htmlspecialchars($output) : $output;
        if ($this->viewTag) {
            return "<$this->viewTag$this->viewStyle>". PHP_EOL . $str . PHP_EOL ."</$this->viewTag>";
        } else {
            return $str;
        }
    }
}
