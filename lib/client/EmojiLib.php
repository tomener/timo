<?php
/**
 * Created by timophp
 * User: tomener
 */

namespace lib\client;


class EmojiLib
{
    /**
     * 过略Emoji表情
     *
     * @param $str
     * @return string
     */
    public static function filter($str)
    {
        $ret = '';
        $str = trim($str);
        if (!empty($str)) {
            $len = mb_strlen($str);
            for ($i = 0; $i < $len; $i++) {
                if (strlen(mb_substr($str, $i, 1)) >= 4) {
                    continue;
                }
                $ret .= mb_substr($str, $i, 1);
            }
        }
        return $ret;
    }

    /**
     * 检测是否有Emoji表情
     *
     * @param $str
     * @return bool true有 false没有
     */
    public static function checkHasEmoji($str)
    {
        $len = mb_strlen($str);
        for ($i = 0; $i < $len; $i++) {
            if (strlen(mb_substr($str, $i, 1)) >= 4) {
                return true;
            }
        }
        return false;
    }
}


function test($name)
{
    echo $name . PHP_EOL;
}