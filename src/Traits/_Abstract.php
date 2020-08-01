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

    public function _debugView($output)
    {
        $str = $this->htmlSpecialChars ? htmlspecialchars($output) : $output;
        if ($this->viewTag) {
            echo "<$this->viewTag$this->viewStyle>". PHP_EOL . $str . PHP_EOL ."</$this->viewTag>";
        } else {
            echo $str;
        }
    }
}
