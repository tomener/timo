<?php
/**
 * Created by timophp
 * User: tomener
 */

namespace lib\format;


use Timo\Config\Config;

class Format
{
    /**
     * 格式化为存储格式（单位分）
     *
     * @param $price
     * @return int
     */
    public static function storePrice($price)
    {
        return intval(bcmul(floatval($price), 100));
    }

    /**
     * 格式化为显示格式（单位元）
     *
     * @param $price
     * @return float|int
     */
    public static function showPrice($price)
    {
        return $price / 100;
    }

    /**
     * 格式化为key value的hash模式
     *
     * @param $key_name
     * @param $value_name
     * @param $data
     * @return array
     */
    public static function hash($key_name, $value_name, &$data)
    {
        $hash = [];
        if (empty($data)) {
            return $hash;
        }
        foreach ($data as $row) {
            if (!empty($value_name)) {
                $hash[$row[$key_name]] = $row[$value_name];
            } else {
                $key = $row[$key_name];
                unset($row[$key_name]);
                $hash[$key] = $row;
            }
        }
        return $hash;
    }

    public static function now()
    {
        return date('Y-m-d H:i:s');
    }

    public static function date()
    {
        return date('Y-m-d');
    }

    /**
     * 正确显示日期
     *
     * @param $datetime
     * @return string
     */
    public static function dateShow($datetime)
    {
        return $datetime > 0 ? date('Y-m-d H:i', $datetime) : '';
    }

    public static function fileSize($size)
    {
        $res = '';
        if ($size < 1024) {
            $res = round($size, 2) . 'B';
        } else if ($size < 1048576) {
            $res = round($size / 1024, 2) . 'K';
        } else if ($size < 1073741824) {
            $res = round($size / 1048576, 2) . 'M';
        } else {
            $res = round($size / 1073741824, 2) . 'G';
        }
        return $res;
    }

    /**
     * 格式化活动日期
     *
     * @param $start_time
     * @param $end_time
     * @return string
     */
    public static function activityTime($start_time, $end_time): string
    {
        if (date("Y-m-d", $start_time) == date("Y-m-d", $end_time)) { //同一天
            $weekday = Format::weekday($start_time);
            $datetime = date("n月j日", $start_time) . "(" . $weekday . ")" . " " . date("H:i", $start_time) . "-" . date("H:i", $end_time);
        } else {
            $weekday1 = Format::weekday($start_time);
            $weekday2 = Format::weekday($end_time);
            $datetime = date("n月j日", $start_time) . "(" . $weekday1 . ") " . date("H:i", $start_time) . "-" . date("n月j日", $end_time) . "(" . $weekday2 . ") " . date("H:i", $end_time);
        }
        return $datetime;
    }

    public static function url($app)
    {
        $format = Config::runtime('static_h5');
        return sprintf($format, $app);
    }

    /**
     * 格式化星期
     *
     * @param $time
     * @return mixed|string
     */
    public static function weekday($time)
    {
        if (is_numeric($time)) {
            $weekday = array('周日', '周一', '周二', '周三', '周四', '周五', '周六');
            return $weekday[date('w', $time)];
        }
        return '';
    }

    /**
     * 格式化过期时间
     *
     * @param $out_time
     * @return string
     */
    public static function expireTime($out_time)
    {
        $diff_time = $out_time - NOW_TIME;
        $days = intval($diff_time / 86400);
        $out_time = $days . '天';
        if ($days == 0) {
            $hours = intval($diff_time / 3600);
            $out_time = $hours . '小时';
            if ($hours == 0) {
                $minutes = intval($diff_time / 60);
                $out_time = $minutes . '分钟';
                if ($minutes == 0) {
                    $out_time = $diff_time . '秒';
                }
            }
        }
        return $out_time;
    }

    /**
     * 时间可读性
     *
     * @param $timestamp
     * @return string
     */
    public static function readable($timestamp)
    {
        $diff_time = NOW_TIME - $timestamp;
        $days = intval($diff_time / 86400);
        $time_text = $days . '天前';
        if ($days == 0) {
            $hours = intval($diff_time / 3600);
            $time_text = $hours . '小时前';
            if ($hours == 0) {
                $minutes = intval($diff_time / 60);
                $time_text = $minutes . '分钟前';
                if ($minutes == 0) {
                    $time_text = $diff_time . '秒前';
                }
            }
        }
        return $time_text;
    }
}
