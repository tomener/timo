<?php
/**
 * Created by timophp.com
 * User: tomener
 */

namespace lib\storage;



use Intervention\Image\ImageManagerStatic;
use Timo\File\File;

class ImageLib
{
    /**
     * 移动临时文件
     *
     * @param $key
     * @return array
     */
    public static function moveTmpFile($key)
    {
        return StorageLib::moveTmpFile($key, 'image');
    }

    /**
     * 删除图片
     *
     * @param $bucket
     * @param $key
     * @return bool
     */
    public static function delImage($key)
    {
        $image = StorageLib::buildPath('image', $key);
        if (is_file($image)) {
            @unlink($image);
        }
        return true;
    }

    /**
     * 删除缩量图
     *
     * @param $key
     * @return bool
     */
    public static function delThumb($key)
    {
        $image = StorageLib::buildPath('image', 'thumb' . DS . $key);
        if (is_file($image)) {
            @unlink($image);
        }
        return true;
    }

    /**
     * 生成缩略图
     *
     * @param $bucket
     * @param $key
     */
    public static function genThumb($key)
    {
        $src_image = StorageLib::buildPath('image', $key);
        $thumb = 'thumb/' . $key;
        $dist_image = StorageLib::buildPath('image', $thumb);
        File::mkDir(dirname($dist_image));

        // resize image to new width but do not exceed original size
        $img = ImageManagerStatic::make($src_image)->widen(600, function ($constraint) {
            $constraint->upsize();
        });
        $img->save($dist_image, 80);
    }

    /**
     * 处理文章内容图片
     *
     * @param $content
     * @param $old_content
     * @return string|string[]
     */
    public static function contentImageHandle($content, $old_content)
    {
        $tmp_images = static::matchTmpImages($content);
        foreach ($tmp_images as $tmp_image) {
            static::moveTmpFile($tmp_image);
        }

        $content = str_replace(StorageLib::tmpDomain('image'), StorageLib::domain('image'), $content);

        $new_images = static::matchRealImages($content);
        $old_images = static::matchRealImages($old_content);

        foreach ($old_images as $old_image) {
            if (!in_array($old_image, $new_images)) {
                static::delImage($old_image);
            }
        }

        return $content;
    }

    /**
     * 匹配临时图片地址
     *
     * @param $content
     * @return array
     */
    public static function matchTmpImages($content)
    {
        preg_match_all('@<img.*?src=[\"|\']?' . StorageLib::tmpDomain('image') . '(.*?)[\"|\']?\s.*?>@i', $content, $matches);
        return $matches[1];
    }

    /**
     * 匹配正式图片地址
     *
     * @param $content
     * @return array
     */
    public static function matchRealImages($content)
    {
        preg_match_all('@<img.*?src=[\"|\']?' . StorageLib::domain('image') . '(.*?)[\"|\']?\s.*?>@i', $content, $matches);
        return $matches[1];
    }
}
