<?php
/**
 * Created by timophp.com
 * User: tomener
 */

namespace app\api\controller;


use Timo\Core\App;
use Timo\Core\Request;
use model\user\User as UserModel;

class User extends Base
{
    /**
     * 用户列表
     *
     * @return array|string
     */
    public function list()
    {
        $p = Request::getInt('p', 1); //分页

        $page = ['limit' => 20, 'p' => $p];

        $users = UserModel::where(['status' => ['between', 0, 1]])
            ->fields('id, name, nickname, avatar')
            ->order('id DESC')
            ->page($page)
            ->select();

        return App::result(0, 'ok', [
            'users' => $users
        ]);
    }

    public function info()
    {
        $uid = Base::$u['uid'];

        $user = UserModel::find($uid, 'id, name, nickname, avatar');

        return App::result(0, 'ok', [
            'uid' => $uid,
            'user' => $user
        ]);
    }
}
