<?php
/**
 * Created by timophp.com
 * User: tomener
 */

namespace lib\cache;


use Timo\Config\Config;
use \Exception;
use Timo\Log\Log;

/**
 * @method static \Redis cache
 * @method static \Redis queue
 * @method static \Redis store
 * @method static \Redis token
 * @method static \Redis gcache
 */
class CacheLib
{
    protected static $instances = [];

    /**
     * 获取缓存key配置
     *
     * @param $config_name
     * @param array ...$key
     * @return string
     * @throws Exception
     */
    public static function getCacheKey($config_name, ...$key)
    {
        static $config = null;
        if (null === $config) {
            $config = Config::load(Config::runtime('config.path') . 'cache_key.config.php');
        }

        $cache_key = $config[$config_name];
        if (!$cache_key) {
            throw new Exception("未定义的缓存key: {$config_name}");
        }

        if (!empty($key)) {
            return sprintf('%s:%s', $cache_key, implode(':', $key));
        }

        return $cache_key;
    }

    /**
     * 获取redis实例
     *
     * @param $dbName
     * @return \Redis
     */
    public static function getRedisInstance($dbName)
    {
        if (isset(self::$instances[$dbName])) {
            return self::$instances[$dbName];
        }
        $config = Config::runtime('redis.' . $dbName);
        try {
            $redis = new \Redis();
            $redis->connect($config['host'], $config['port'], $config['timeout']);
            $redis->select($config['db']);
        } catch (\RedisException $e) {
            unset(self::$instances[$dbName]);
            Log::single([
                'Message' => $e->getMessage(),
                'Code' => $e->getCode(),
                'File' => $e->getFile(),
                'Line' => $e->getLine()
            ], 'redis/getRedisInstance');
        }
        self::$instances[$dbName] = $redis;
        return $redis;
    }

    /**
     * 销毁redis连接对象
     *
     * @param null $dbName
     */
    public static function destroy($dbName = null)
    {
        if ($dbName == null) {
            self::$instances = [];
        } else {
            if (isset(self::$instances[$dbName])) {
                unset(self::$instances[$dbName]);
            }
        }
    }

    /**
     * @param $name
     * @param $arguments
     * @return bool|mixed|\Redis
     */
    public static function __callStatic($name, $arguments)
    {
        switch ($name) {
            case 'cache' :
            case 'queue' :
            case 'store' :
                return self::getRedisInstance($name);
            default :
                return false;
        }
    }
}
