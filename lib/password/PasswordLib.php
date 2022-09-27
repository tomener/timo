<?php


namespace lib\password;


use Timo\Config\Config;

class PasswordLib
{
    public static function gen($password)
    {
        return md5(md5($password . Config::runtime('pwd_salt')));
    }

    public static function validate($password)
    {
        if (mb_strlen($password) < 6 && !empty($password)) {
            return '密码不能少于6个字符';
        }

        if (is_numeric($password)) {
            return  '密码不能为纯数字';
        }

        return null;
    }
}
