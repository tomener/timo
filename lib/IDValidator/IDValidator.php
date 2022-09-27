<?php

namespace lib\IDValidator;

class IDValidator
{
    private static $GB2260;
    private static $instance;
    private static $cache = [];
    private static $util;

    function __construct()
    {
        self::$GB2260 = GB2260::getGB2260();
        self::$util = util::getInstance();
    }

    public static function make()
    {
        if (is_null(self::$instance)) {
            self::$instance = new IDValidator ();
        }
        return self::$instance;
    }

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new IDValidator ();
        }
        return self::$instance;
    }

    function isValid($id)
    {
        $code = self::$util->checkArg($id);
        if ($code === false) {
            return false;
        }

        //查询cache
        if (isset(self::$cache[$id]) && isset(self::$cache[$id]['valid'])) {
            return self::$cache[$id]['valid'];
        }

        //基本格式判断
        $length = strlen($id);
        if ($length === 15) {
            //15位身份证没有字母
            if (!is_numeric($id)) {
                return false;
            }
        } else if ($length === 18) {
            //基本格式校验
            if (!preg_match('/^\d{17}[0-9xX]$/', $id)) {
                return false;
            }
        } else {
            return false;
        }

        $addr = substr($code['body'], 0, 6);
        $birth = $code['type'] === 18 ? substr($code['body'], 6, 8) : substr($code['body'], 6, 6);
        $order = substr($code['body'], -3);

        if (!(self::$util->checkAddr($addr) && self::$util->checkBirth($birth) && self::$util->checkOrder($order))) {
            self::$cache[$id]['valid'] = false;
            return false;
        }

        // 15位不含校验码，到此已结束
        if ($code ['type'] === 15) {
            self::$cache[$id]['valid'] = true;
            return true;
        }

        /* 校验位部分 */

        // 位置加权
        $posWeight = array();
        for ($i = 18; $i > 1; $i--) {
            $wei = self::$util->weight($i);
            $posWeight[$i] = $wei;
        }

        // 累加body部分与位置加权的积
        $bodySum = 0;
        $bodyArr = str_split($code['body']);
        for ($j = 0; $j < count($bodyArr); $j++) {
            $bodySum += (intval($bodyArr[$j], 10) * $posWeight[18 - $j]);
        }

        // 得出校验码
        $checkBit = 12 - ($bodySum % 11);
        if ($checkBit == 10) {
            $checkBit = 'X';
        } else if ($checkBit > 10) {
            $checkBit = $checkBit % 11;
        }
        // 检查校验码
        if ($checkBit != $code['checkBit']) {
            self::$cache[$id]['valid'] = false;
            return false;
        } else {
            self::$cache[$id]['valid'] = true;
            return true;
        }
    }

    // 分析详细信息
    function getInfo($id)
    {
        // 号码必须有效
        if ($this->isValid($id) === false) {
            return false;
        }

        $code = self::$util->checkArg($id);

        // 查询cache
        // 到此时通过isValid已经有了cache记录
        if (isset(self::$cache[$id]) && isset(self::$cache[$id]['info'])) {
            return self::$cache[$id]['info'];
        }

        $addr = substr($code['body'], 0, 6);
        $birth = ($code['type'] === 18 ? substr($code['body'], 6, 8) :
            substr($code['body'], 6, 6));
        $order = substr($code['body'], -3);

        $info = array();
        $info['addrCode'] = $addr;
        if (self::$GB2260 !== null) {
            $info['addr'] = self::$util->getAddrInfo($addr);
        }
        $info ['birth'] = ($code ['type'] === 18 ? (substr($birth, 0, 4) . '-' . substr($birth, 4, 2) . '-' . substr($birth, -2)) : ('19' . substr($birth, 0, 2) . '-' . substr($birth, 2, 2) . '-' . substr($birth, -2)));
        $info['sex'] = ($order % 2 === 0 ? 2 : 1);
        $info['length'] = $code['type'];
        if ($code['type'] === 18) {
            $info['checkBit'] = $code['checkBit'];
        }

        // 记录cache
        self::$cache[$id]['info'] = $info;

        return $info;
    }

    /**
     * 获取性别
     *
     * @param $id
     * @return bool|int
     */
    public function getGender($id)
    {
        // 号码必须有效
        if ($this->isValid($id) === false) {
            return false;
        }

        $code = self::$util->checkArg($id);
        $order = substr($code['body'], -3);
        return ($order % 2 === 0 ? 2 : 1);
    }

    /**
     * 计算年龄
     *
     * @param $id
     * @param $date
     * @return false|int
     */
    public function getAge($id, $date = '')
    {
        $time = !empty($date) ? strtotime($date) : time();

        // 号码必须有效
        if ($this->isValid($id) === false) {
            return false;
        }

        $id = $this->idCard15To18($id);

        $y = (int)substr($id, 6, 4);
        $nd = (int)substr($id, 10, 4);
        $age = date('Y', $time) - $y;
        if ((int)date('nd', $time) < $nd) {
            $age--;
        }
        return intval($age);
    }

    // 将15位身份证升级到18位
    public function idCard15To18($id)
    {
        if (strlen($id) == 18) {
            return $id;
        }
        if (strlen($id) != 15) {
            return false;
        } else {
            // 如果身份证顺序码是996 997 998 999，这些是为百岁以上老人的特殊编码
            if (array_search(substr($id, 12, 3), array('996', '997', '998', '999')) !== false) {
                $id = substr($id, 0, 6) . '18' . substr($id, 6, 9);
            } else {
                $id = substr($id, 0, 6) . '19' . substr($id, 6, 9);
            }
        }
        $id .= self::buildCheckCode($id);
        return $id;
    }

    // 计算身份证校验码，根据国家标准GB 11643-1999
    public static function buildCheckCode($id_card_base)
    {
        if (strlen($id_card_base) != 17) {
            return false;
        }
        //加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        //校验码对应值
        $verify_number_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        $checksum = 0;
        for ($i = 0; $i < strlen($id_card_base); $i++) {
            $checksum += substr($id_card_base, $i, 1) * $factor[$i];
        }
        $mod = $checksum % 11;
        $verify_number = $verify_number_list[$mod];
        return $verify_number;
    }

    /**
     * 获取生日
     *
     * @param $id
     * @return string
     */
    public function getBirthday($id)
    {
        // 号码必须有效
        if ($this->isValid($id) === false) {
            return '';
        }

        $code = self::$util->checkArg($id);

        $birth = ($code['type'] === 18 ? substr($code['body'], 6, 8) : substr($code['body'], 6, 6));
        return ($code ['type'] === 18 ? (substr($birth, 0, 4) . '-' . substr($birth, 4, 2) . '-' . substr($birth, -2)) : ('19' . substr($birth, 0, 2) . '-' . substr($birth, 2, 2) . '-' . substr($birth, -2)));
    }

    // 仿造一个号
    function makeID($isFifteen = false)
    {
        // 地址码
        $addr = null;
        if (self::$GB2260 !== null) {
            $loopCnt = 0;
            while ($addr === null) {
                // 防止死循环
                if ($loopCnt > 50) {
                    $addr = 110101;
                    break;
                }
                $prov = self::$util->str_pad(self::$util->rand(66), 2, '0');
                $city = self::$util->str_pad(self::$util->rand(20), 2, '0');
                $area = self::$util->str_pad(self::$util->rand(20), 2, '0');
                $addrTest = $prov . $city . $area;
                if (isset(self::$GB2260[$addrTest])) {
                    $addr = $addrTest;
                    break;
                }
                $loopCnt++;
            }
        } else {
            $addr = 110101;
        }

        // 出生年
        $yr = self::$util->str_pad(self::$util->rand(99, 50), 2, '0');
        $mo = self::$util->str_pad(self::$util->rand(12, 1), 2, '0');
        $da = self::$util->str_pad(self::$util->rand(28, 1), 2, '0');
        if ($isFifteen) {
            return $addr . $yr . $mo . $da
                . self::$util->str_pad(self::$util->rand(999, 1), 3, '1');
        }

        $yr = '19' . $yr;
        $body = $addr . $yr . $mo . $da . self::$util->str_pad(self::$util->rand(999, 1), 3, '1');

        // 位置加权
        $posWeight = array();
        for ($i = 18; $i > 1; $i--) {
            $wei = self::$util->weight($i);
            $posWeight[$i] = $wei;
        }

        // 累加body部分与位置加权的积
        $bodySum = 0;
        $bodyArr = str_split($body);
        for ($j = 0; $j < count($bodyArr); $j++) {
            $bodySum += (intval($bodyArr[$j], 10) * $posWeight[18 - $j]);
        }

        // 得出校验码
        $checkBit = 12 - ($bodySum % 11);
        if ($checkBit == 10) {
            $checkBit = 'X';
        } else if ($checkBit > 10) {
            $checkBit = $checkBit % 11;
        }
        return ($body . $checkBit);
    }
}
