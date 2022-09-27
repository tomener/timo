<?php
/**
 * Created by timophp.com
 * User: tomener
 */

namespace app\api\controller;


use Timo\Core\App;

class Index
{
    public function index()
    {
        return App::result(0, 'TimoPHP Version:' . VERSION . ' Environment:' . ENV);
    }
}
