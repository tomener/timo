<?php
/**
 * Created by timophp
 * User: tomener
 */

namespace lib\encrypt;


use Timo\Config\Config;
use Timo\Helper\Helper;

class AesEncrypt
{
    /**
     * @var string 加密密钥（16位|32位）
     */
    protected $key;

    /**
     * 加密算法
     *
     * @var string AES-128-CBC|AES-256-CBC
     */
    protected $method;

    /**
     * @var int 过期时间，默认不过期
     */
    protected $expire = 0;

    protected static $container;

    public function __construct($key, $method)
    {
        $this->key = $key;
        $this->method = $method;
    }

    /**
     * @param null $config
     * @return AesEncrypt
     * @throws \Exception
     */
    public static function builder($config = null)
    {
        if (is_null($config)) {
            $config = Config::runtime('encryption.aes');
        }
        if (is_null($config)) {
            throw new \Exception('please config the aes encryption key.');
        }

        $name = md5($config['key'] . $config['method']);

        if (!isset(self::$container[$name])) {
            self::$container[$name] = new self($config['key'], $config['method']);
        }

        return self::$container[$name];
    }

    /**
     * aes加密
     *
     * @param $data
     * @return string
     */
    public function encrypt($data)
    {
        $expire_timestamp = $this->expire > 0 ? (time() + $this->expire) : 0;

        $data = $data . '|' . $expire_timestamp;

        $iv = Helper::random(16);
        $data = openssl_encrypt($data, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);

        $this->expire = 0;

        return $iv . base64_encode($data);
    }

    /**
     * aes解密
     *
     * @param $data
     * @return array
     */
    public function decrypt($data)
    {
        try {
            $iv = substr($data, 0, 16);
            if (strlen($iv) != 16) {
                return [1, '数据格式错误', null];
            }
            $data = base64_decode(substr($data, 16));

            $data = openssl_decrypt($data, $this->method, $this->key, OPENSSL_RAW_DATA, $iv);
            $data = explode('|', $data);

            if (!isset($data[1])) {
                return [1, '数据格式错误', null];
            }
            $expire_timestamp = intval($data[1]);
            if ($expire_timestamp > 0 && time() > $expire_timestamp) {
                return [-1, '数据过期', null];
            }

            return [0, 'ok', $data[0]];
        } catch (\Exception $e) {
            return [1, '数据异常', null];
        }
    }

    /**
     * 设置过期时间
     *
     * @param $expire
     */
    public function expire($expire)
    {
        $this->expire = $expire;
        return $this;
    }
}
