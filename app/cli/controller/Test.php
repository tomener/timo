<?php
/**
 * Created by timophp
 * User: tomener
 */

namespace app\cli\controller;


use lib\token\TokenLib;
use service\user\UserService;

class Test
{
    public function timo()
    {
        echo "\nTimo run Success in: " . "\033[0;32m" . ENV . "\033[0m" . ' environment.' . PHP_EOL;
    }

    public function token()
    {
        list($token, $expire) = UserService::login(1, 1);
        echo $token . PHP_EOL;

        $ret = TokenLib::validate($token);
        var_dump($ret);
    }
}
