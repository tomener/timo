<?php
/**
 * Created by timophp
 * User: tomener
 */

namespace lib\qiniu;


use Qiniu\Auth;
use Qiniu\Http\Client;
use function Qiniu\base64_urlSafeEncode;
use Qiniu\Processing\PersistentFop;
use Qiniu\Storage\BucketManager;
use Timo\Config\Config;


class QiniuLib
{
    /**
     * @var Auth 鉴权对象
     */
    protected static $auth;

    protected static $resources;

    /**
     * 移动临时文件
     *
     * @param $key1
     * @param $bucket2
     * @param null $key2
     * @return null|\Qiniu\Http\Error
     */
    public static function moveTmpFile($key1, $bucket2, $key2 = null)
    {
        if (is_null($key2)) {
            $key2 = $key1;
        }
        self::storeKey($key2);
        list($ret, $err) = self::moveQiNiuFile('tmp', $key1, $bucket2, $key2);
        return $err;
    }

    /**
     * 移动文件
     *
     * @param $bucket1
     * @param $key1
     * @param $bucket2
     * @param $key2
     * @return array
     */
    public static function moveQiNiuFile($bucket1, $key1, $bucket2, $key2)
    {
        $bucket1 = self::getBucket($bucket1);
        $bucket2 = self::getBucket($bucket2);

        //初始化签权对象
        $auth = self::getAuth();

        //初始化BucketManager
        $bucketMgr = new BucketManager($auth);

        $err = $bucketMgr->move($bucket1, $key1, $bucket2, $key2);
        return $err;
    }

    /**
     * 复制文件
     *
     * @param $bucket1
     * @param $key1
     * @param $bucket2
     * @param $key2
     * @return mixed
     */
    public static function copyFile($bucket1, $key1, $bucket2, $key2)
    {
        $bucket1 = self::getBucket($bucket1);
        $bucket2 = self::getBucket($bucket2);

        //初始化签权对象
        $auth = self::getAuth();

        //初始化BucketManager
        $bucketMgr = new BucketManager($auth);

        self::storeKey($key1);
        self::storeKey($key2);
        $err = $bucketMgr->copy($bucket1, $key1, $bucket2, $key2);
        return $err;
    }

    /**
     * 批量复制
     *
     * @param $srcBucket
     * @param $keyPairs
     * @param $destBucket
     * @return mixed
     */
    public static function batchCopy($srcBucket, $keyPairs, $destBucket)
    {
        $srcBucket = self::getBucket($srcBucket);
        $destBucket = self::getBucket($destBucket);

        //初始化签权对象
        $auth = self::getAuth();

        //初始化BucketManager
        $bucketManager = new BucketManager($auth);

        self::storeKey($keyPairs);

        $ops = $bucketManager->buildBatchCopy($srcBucket, $keyPairs, $destBucket, true);
        list($ret, $err) = $bucketManager->batch($ops);
        return $err;
    }

    /**
     * 组装七牛地址
     *
     * @param $bucket
     * @param $key
     * @return string
     */
    public static function buildUrl($bucket, $key)
    {
        return self::getResource($bucket) . $key;
    }

    /**
     * 抓取外部图片
     *
     * @param $bucket
     * @param $url
     * @param $key
     * @return bool
     * @throws \Exception
     */
    public static function fetch($bucket, $url, $key)
    {
        //构建鉴权对象
        $auth = QiniuLib::getAuth();

        //要上传的空间
        $bucket = QiniuLib::getBucket($bucket);
        $bucketManager = new BucketManager($auth);

        //指定抓取的文件保存名称
        self::storeKey($key);
        list(, $err) = $bucketManager->fetch($url, $bucket, $key);
        if ($err !== null) {
            return false;
        }
        return true;
    }

    /**
     * 删除文件
     *
     * @param $bucket
     * @param $key
     * @return array
     * @throws \Exception
     */
    public static function deleteQiNiuFile($bucket, $key)
    {
        //初始化签权对象
        $auth = self::getAuth();

        //初始化BucketManager
        $bucketMgr = new BucketManager($auth);

        self::storeKey($key);
        $err = $bucketMgr->delete(self::getBucket($bucket), $key);
        return $err;
    }

    /**
     * 批量删除
     *
     * @param $bucket
     * @param $keys
     * @return mixed
     */
    public static function batchDelete($bucket, $keys)
    {
        //初始化签权对象
        $auth = self::getAuth();

        //初始化BucketManager
        $bucketManager = new BucketManager($auth);

        self::storeKey($keys);
        $ops = $bucketManager->buildBatchDelete($bucket, $keys);
        list($ret, $err) = $bucketManager->batch($ops);
        return $err;
    }

    /**
     * 判断文件是否存在
     *
     * @param $bucket
     * @param $key
     * @return bool
     */
    public static function exists($bucket, $key)
    {
        $url = self::buildUrl($bucket, $key) . '?avinfo';

        $response = Client::get($url);
        return $response->statusCode == 200 ? true : false;
    }

    /**
     * 处理音频文件
     *
     * 目前只压缩了32k，以后可以压缩到40k、64k
     *
     * @param $bucket
     * @param $key
     * @return bool
     */
    public static function dealAudio($bucket, $key)
    {
        $src_key = 'src/' . $key;
        self::storeKey($src_key);
        list($ret, $err) = self::moveQiNiuFile('tmp', $key, $bucket, $src_key);
        if ($err) {
            return false;
        }

        $key_32k = '32/' . $key;
        self::storeKey($key_32k);
        list($id, $err) = self::compressMp3File($bucket, $src_key, $bucket, $key_32k);
        if ($err) {
            var_dump(2, $err);
            return false;
        } else {
            return true;
        }
    }

    /**
     * 删除音频
     *
     * @param $bucket
     * @param $audio
     * @return bool
     */
    public static function deleteAudio($bucket, $audio)
    {
        //612是no such file or directory
        $key_32k = '32/' . $audio;
        list($ret, $err) = QiniuLib::deleteQiNiuFile($bucket, $key_32k);
        if ($err && $err->code() != 612) {
            return false;
        }

        $src_key = 'src/' . $audio;
        list($ret, $err) = QiniuLib::deleteQiNiuFile($bucket, $src_key);
        if ($err && $err->code() != 612) {
            return false;
        }

        return true;
    }

    /**
     * 压缩七牛上的Mp3文件
     *
     * @param string $bucket 要压缩文件所在的bucket
     * @param string $src_key 要压缩的文件
     * @param string $save_bucket 压缩过后的文件存放的bucket
     * @param string $save_key 压缩过后的文件
     * @return array
     */
    public static function compressMp3File($bucket, $src_key, $save_bucket, $save_key)
    {
        $bucket = self::getBucket($bucket);
        $save_bucket = self::getBucket($save_bucket);

        //初始化签权对象
        $auth = self::getAuth();

        //转码是使用的队列名称
        $pipeline = 'audio-compress';
        $config = new \Qiniu\Config();
        $fop = new PersistentFop($auth, $config);

        //要进行转码的转码操作
        $fops = "avthumb/mp3/aq/8/ar/24000";
        //$fops = "avthumb/mp3/ab/32k/ar/24000";
        $save_key = base64_urlSafeEncode($save_bucket . ':' . $save_key);

        $fops .= '|saveas/' . $save_key;

        list($id, $err) = $fop->execute($bucket, $src_key, $fops, $pipeline);

        return [$id, $err];
    }

    /**
     * 获取上传token
     *
     * @return string
     */
    public static function getUpToken()
    {
        //初始化签权对象
        $auth = self::getAuth();

        $bucket = self::getBucket('tmp');
        $upToken = $auth->uploadToken($bucket);
        return $upToken;
    }

    /**
     * 获取七牛空间名称
     *
     * @param $bucket
     * @return mixed
     * @throws \Exception
     */
    public static function getBucket($bucket)
    {
        $bucket_conf = Config::runtime('qiniu.bucket');
        if (!isset($bucket_conf[$bucket])) {
            throw new \Exception('qiniu bucket ' . $bucket . ' no exists');
        }
        return $bucket_conf[$bucket];
    }

    /**
     * 获取资源头
     *
     * @param $bucket
     * @return mixed
     * @throws \Exception
     */
    public static function getResource($bucket)
    {
        if (!self::$resources) {
            self::$resources = Config::runtime('qiniu.resource');
        }
        if (!isset(self::$resources[$bucket])) {
            throw new \Exception('qiniu resource ' . $bucket . ' no exists');
        }
        return self::$resources[$bucket] . (Config::runtime('qiniu.prefix') ?? '');
    }

    /**
     * 获取存储在七牛的真实key
     *
     * @param $keys string|array
     */
    public static function storeKey(&$keys)
    {
        $prefix = Config::runtime('qiniu.prefix');
        $prefix = $prefix ?? '';

        if (!is_array($keys)) {
            $keys = $prefix . $keys;
            return;
        }

        $is_idx_arr = key($keys) === 0;
        foreach ($keys as $k => $v) {
            if ($is_idx_arr) {
                $keys[$k] = $prefix . $v;
            } else {
                $keys[$prefix . $k] = $prefix . $v;
                unset($keys[$k]);
            }
        }

        return;
    }

    private static function getKey($type = 'ak')
    {
        $key_conf = Config::runtime('qiniu.key');
        return $key_conf[$type];
    }

    /**
     * 初始化签权对象
     *
     * @return Auth
     */
    public static function getAuth()
    {
        if (!self::$auth) {
            self::$auth = new Auth(self::getKey('ak'), self::getKey('sk'));
        }
        return self::$auth;
    }
}
