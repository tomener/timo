<?php
/**
 * Created by timophp.com
 * User: tomener
 */

namespace service\user;


use lib\token\TokenLib;
use model\user\User;

class UserService
{
    public static function get($uid)
    {
        //从redis缓存中获取用户信息

        return ['uid' => 100, 'nickname' => 'tomener', 'avatar' => 'a/b/c/989378843.jpg'];
    }

    /**
     * 登录
     *
     * @param $uid
     * @param $is_auth
     * @return array
     * @throws \Exception
     */
    public static function login($uid, $is_auth)
    {
        $expire = 86400 * 7;
        $data = [$uid, $is_auth];
        $token = TokenLib::create($data, $expire);

        User::where($uid)->update(['last_login_time' => NOW_TIME]);
        return [$token, $expire];
    }
}
