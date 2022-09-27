<?php
/**
 * Created by timophp
 * User: tomener
 */

namespace lib\token;


use lib\encrypt\AesEncrypt;

class TokenLib
{
    /**
     * 创建Token
     *
     * @param $data int|string|array
     * @param $expire
     * @return string
     * @throws \Exception
     */
    public static function create($data, $expire)
    {
        if (!is_array($data)) {
            $data = [$data];
        }
        $data = json_encode($data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        $token = AesEncrypt::builder()->expire($expire)->encrypt($data);
        return $token;
    }

    public static function validate($token)
    {
        $ret = AesEncrypt::builder()->decrypt($token);
        if ($ret[0] === 0) {
            $ret[2] = json_decode($ret[2], true);
            if (count($ret[2]) == 1) {
                $ret[2] = $ret[2][0];
            }
        }
        return $ret;
    }
}
