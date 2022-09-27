<?php
/**
 * Created by timophp
 * User: tomener
 */

namespace lib\sms;


use AlibabaCloud\Client\AlibabaCloud;
use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use Timo\Config\Config;
use Timo\Log\Log;

class AliSmsLib
{
    private $ram;

    public function __construct()
    {
        $this->ram = Config::runtime('aliyun.ram');
    }

    /**
     * 发送绑定验证码
     *
     * @param $mobile
     * @param $code
     * @return array
     */
    public function userBindVerifyCode($mobile, $code)
    {
        $sign_name = 'TimoPHP';
        $template_code = 'SMS_170000000';
        $template_param = json_encode(['code' => $code]);
        return $this->send($mobile, $sign_name, $template_code, $template_param);
    }

    /**
     * 发送短信
     *
     * @param $mobile
     * @param $sign_name
     * @param $template_code
     * @param $code
     * @return array
     */
    public function send($mobile, $sign_name, $template_code, $template_param)
    {
        AlibabaCloud::accessKeyClient($this->ram['accessKeyId'], $this->ram['accessKeySecret'])
            ->regionId('cn-hangzhou')
            ->asDefaultClient();

        try {
            $result = AlibabaCloud::rpc()
                ->product('Dysmsapi')
                // ->scheme('https') // https | http
                ->version('2017-05-25')
                ->action('SendSms')
                ->method('POST')
                ->host('dysmsapi.aliyuncs.com')
                ->options([
                    'query' => [
                        'RegionId' => "cn-hangzhou",
                        'PhoneNumbers' => $mobile,
                        'SignName' => $sign_name,
                        'TemplateCode' => $template_code,
                        'TemplateParam' => $template_param,
                    ],
                ])
                ->request()->toArray();
            if ($result['Code'] != 'OK') {
                Log::error($result, 'sms/error');
                return [1, '发送失败'];
            }
            return [0, '发送成功'];
        } catch (ClientException $e) {
            Log::error(['Code' => $e->getCode(), 'Message' => $e->getErrorMessage(), 'File' => $e->getFile(), 'Line' => $e->getLine()], 'sms/error');
            return [1, '发送失败'];
        } catch (ServerException $e) {
            Log::error(['Code' => $e->getCode(), 'Message' => $e->getErrorMessage(), 'File' => $e->getFile(), 'Line' => $e->getLine()], 'sms/error');
            return [1, '发送失败'];
        }
    }
}
