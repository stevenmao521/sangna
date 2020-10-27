<?php
 /* 
  * Copyright (C) 2017 All rights reserved.
 *   
 * @File UserTest.php
 * @Brief 
 * @Author 毛子
 * @Version 1.0
 * @Date 2017-12-26
 * @Remark 服务端接口 发送短信
 */
namespace app\common\tool;
use think\Config;
use clt\SignatureHelper;

class Smscode {
    public function send_code($mobile, $txt) {
        $params = array();

        // *** 需用户填写部分 ***
        // fixme 必填：是否启用https
        $security = false;

        // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
        $accessKeyId = "LTAI4usYYD4sJa9w";
        $accessKeySecret = "dzTzE3HVvjFIiHutV4I3JdBsKKXpHl";

        // fixme 必填: 短信接收号码
        $params["PhoneNumbers"] = $mobile;

        // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        $params["SignName"] = "小庆电器";

        // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        #$params["TemplateCode"] = "SMS_152240098";
        $params["TemplateCode"] = "SMS_205060414";

        // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
        
        $mobile_code = mz_random(4,1);
        $params['TemplateParam'] = Array(
            "name" => $txt
        );
        // fixme 可选: 设置发送短信流水号
        $params['OutId'] = "12345";

        // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
        $params['SmsUpExtendCode'] = "1234567";


        // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
        if (!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
            $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
        }

        // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
        $helper = new SignatureHelper();

        // 此处可能会抛出异常，注意catch
        $content = $helper->request(
                $accessKeyId, $accessKeySecret, "dysmsapi.aliyuncs.com", array_merge($params, array(
            "RegionId" => "cn-hangzhou",
            "Action" => "SendSms",
            "Version" => "2017-05-25",
                )), $security
        );
        if ($content->Code == 'OK') {
            return 1;
        } else {
            return 2;
        }
    }
    
}