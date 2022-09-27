<?php
/**
 * Created by timophp.com
 * User: tomener
 */

namespace app\api\controller;


use lib\token\TokenLib;
use Timo\Core\App;
use Timo\Core\Request;
use Timo\Core\Response;

class Base
{
    public static $u;

    public function __construct()
    {
        if (Request::method() == 'OPTIONS') {
            Response::send();
        }

        self::checkToken();
    }

    /**
     * 验证TOKEN有效性
     *
     * @return bool
     * @throws \Exception
     */
    private static function checkToken()
    {
        $token = Request::getHeaders('Token');
        if (is_null($token)) {
            Response::send(App::result(100403, 'user invalid'));
        }

        list($code, $msg, $data) = TokenLib::validate($token);
        if ($code == -1) {
            Response::send(App::result(100403, 'token expire'));
        } elseif ($code != 0) {
            Response::send(App::result(100403, 'token error'));
        }

        Base::$u = [
            'uid' => $data[0],
            'is_auth' => $data[1]
        ];

        return true;
    }
}
