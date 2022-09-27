<?php
/**
 * Created by timophp.com
 * User: tomener
 */

namespace lib\storage;


use Timo\Config\Config;
use Timo\File\File;

class StorageLib
{
    /**
     * 移动临时文件
     *
     * @param $key
     * @return array
     */
    public static function moveTmpFile($key, $buket)
    {
        $tmp_file = StorageLib::buildPath('tmp', $key);
        $dist_file = StorageLib::buildPath($buket, $key);

        if (!is_file($tmp_file)) {
            return [1, '临时文件不存在'];
        }

        File::mkDir(dirname($dist_file));
        $ret = rename($tmp_file, $dist_file);
        if ($ret === false) {
            return [1, '移动失败'];
        }
        return [0, '移动成功'];
    }

    /**
     * 组装目录
     *
     * @param $bucket
     * @param $env
     * @return string
     */
    public static function buildDir($bucket)
    {
        return ROOT_PATH . 'storage' . DS . $bucket . DS;
    }

    /**
     * 组装资源路径
     *
     * @param $bucket
     * @param $key
     * @return string
     */
    public static function buildPath($bucket, $key)
    {
        return ROOT_PATH . 'storage' . DS . $bucket . DS . $key;
    }

    /**
     * 组装资源路径
     *
     * @param $bucket
     * @param $key
     * @return string
     */
    public static function buildTmpPath($key)
    {
        return ROOT_PATH . 'storage' . DS . 'tmp' . DS . $key;
    }

    /**
     * 正式图片、音频域名
     *
     * @param $bucket
     * @param string $dir
     * @return string
     */
    public static function domain($bucket, $dir = '')
    {
        return Config::runtime('wx_url') . '/' . $bucket . '/s/' . (!empty($dir) ? $dir . '/' : '');
    }

    /**
     * 临时图片域名
     *
     * @param $bucket
     * @return string
     */
    public static function tmpDomain($bucket)
    {
        return Config::runtime('wx_url') . '/' . $bucket . '/t/';
    }
}
