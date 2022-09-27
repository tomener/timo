<?php
/**
 * Created by timophp.com
 * User: tomener
 */

namespace lib\storage;


use Timo\Core\Request;
use Timo\File\File;

class AudioLib
{
    protected static $command_dir = ENV != 'dev' ? '/usr/local/ffmpeg/' : '';

    protected static $audioInfo = [];

    /**
     * 移动临时文件
     *
     * @param $key
     * @return array
     */
    public static function moveTmpFile($key)
    {
        $tmp_file = StorageLib::buildTmpPath($key);
        $dist_file = static::buildPath('audio', 'src', $key);

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
     * 压缩音频
     *
     * @param $audio
     */
    public static function compress($key)
    {
        $src_audio = static::buildPath('audio', 'src', $key);
        $dist_audio = static::buildPath('audio', '32', $key);
        File::mkDir(dirname($dist_audio));
        $bit_rate = static::getBitRate('audio', 'src', $key);
        $bit_rate = $bit_rate > 128000 ? ' -b:a 128k -bufsize 128k' : '';
        $command = static::$command_dir . 'ffmpeg -i ' . $src_audio . ' -ar 24000' . $bit_rate . ' -y ' . $dist_audio;
        exec($command);
    }

    /**
     * 删除音频
     *
     * @param $bucket
     * @param $key
     * @return bool
     */
    public static function delAudio($key)
    {
        $audio_src = static::buildPath('audio', 'src', $key);
        if (is_file($audio_src)) {
            @unlink($audio_src);
        }

        $audio_32k = static::buildPath('audio', '32', $key);
        if (is_file($audio_32k)) {
            @unlink($audio_32k);
        }
        return true;
    }

    /**
     * 获取音频比特率
     *
     * @param $key
     * @return int|mixed
     */
    public static function getBitRate($bucket, $dir, $key)
    {
        $info = static::getStreamInfo($bucket, $dir, $key);
        return isset($info['bit_rate']) ? $info['bit_rate'] : 0;
    }

    /**
     * 获取音频时长
     *
     * @param $key
     * @return int|mixed
     */
    public static function getDuration($bucket, $dir, $key)
    {
        $info = static::getStreamInfo($bucket, $dir, $key);
        return isset($info['duration']) ? ceil($info['duration']) : 0;
    }

    /**
     * 获取音频流信息
     *
     * @param $bucket
     * @param $dir
     * @param $key
     * @return mixed
     */
    public static function getStreamInfo($bucket, $dir, $key)
    {
        $hash = md5($key);
        if (!isset(static::$audioInfo[$hash])) {
            ob_start();
            $audio = static::buildPath($bucket, $dir, $key);
            passthru(static::$command_dir . 'ffprobe -v quiet -print_format json -show_streams ' . $audio);
            $data = json_decode(ob_get_clean(), true);
            static::$audioInfo[$hash] = isset($data['streams']) ? $data['streams'][0] : [];
        }
        return static::$audioInfo[$hash];
    }

    /**
     * 以文件流输出音频文件
     *
     * @param $filePath string 文件地址
     * @return bool
     */
    public static function outputStream($filepath)
    {
        if (!file_exists($filepath)) {
            return false;
        }
        //返回的文件(流形式)
        //对照的完整地址推荐：http://tool.oschina.net/commons/
        $mime = mime_content_type($filepath);
        header("Content-Type:" . $mime);
        header("Age:300");
        header('Accept-Ranges: bytes'); //按照字节大小返回

        $fileSize = filesize($filepath);
        $httpRange = Request::getHeaders('Range');
        if (!is_null($httpRange)) {
            header("HTTP/1.1 206 Partial Content");
            list($name, $range) = explode("=", $httpRange);
            list($begin, $end) = explode("-", $range);
            if (empty($begin)) {
                $begin = 0;
            }
            if (empty($end) || $end == 0) {
                $end = $fileSize - 1;
            }
        } else {
            $begin = 0;
            $end = $fileSize - 1;
        }
        header("Content-Range: bytes " . $begin . "-" . $end . "/" . $fileSize);
        header("Content-Length: " . ($end - $begin + 1));
        //header("Content-Disposition: filename=".basename($filePath));
        //header("Content-Disposition: filename=".time().'.mp3');
        //header("Content-Range:bytes {$begin}-{$end}/{$fileSize}");
        header("Cache-Control:max-age=2592000");
        $file_obj = fopen($filepath, 'rb');
        ob_clean();
        flush();
        fseek($file_obj, $begin);
        //设置分流
        $buffer = 1024 * 10;
        //来个文件字节计数器
        $count = 0;
        while (!feof($file_obj)) {
            $p = min($buffer, $end - $begin + 1);
            $begin += $p;
            $data = fread($file_obj, $buffer);
            //$count += $buffer;//本次请求流量统计
            echo $data;    //传数据给浏览器端
        }
        fclose($file_obj);
        return true;
    }

    /**
     * 组装音频路径
     *
     * @param $bucket string
     * @param $dir
     * @param $key
     * @return string
     */
    public static function buildPath($bucket, $dir, $key)
    {
        return StorageLib::buildDir($bucket) . $dir . DS . $key;
    }
}
