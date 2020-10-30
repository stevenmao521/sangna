<?php
 /* 
  * Copyright (C) 2017 All rights reserved.
 *   
 * @File UserTest.php
 * @Brief 
 * @Author 毛子
 * @Version 1.0
 * @Date 2017-12-26
 * @Remark 服务端接口
 */
namespace app\api\controller;
use think\Config;

class Buy extends Common{
    
    public function _initialize() {
        parent::_initialize();
    }
    
    public function pay() {
        $id = input("id");
        $info = db("streetgirl")->where("id='{$id}'")->find();
        if (!$info) {
            $this->error("信息不存在");
        }
        return $this->fetch("",[
            "info"=>$info,
            "id"=>$id
        ]);
    }
    
    #创建订单
    public function createOrder() {
        #生成系统订单
        $id = input("id");
        $email = input("email");
        if (!$id || !$email) {
            return mz_apierror("参数缺失");
        }
        $info = db("streetgirl")->where("id='{$id}'")->find();
        if (!$info) {
            return mz_apierror("信息不存在");
        }
        if ($info['price'] <= 0) {
            return mz_apierror("金额错误");
        }
        
        $ip = getIp();
        $tuserid = session($ip);
        
        $order_sn = mz_get_order_sn();
        $res = db("orders")->insert([
            "uid"=>$tuserid,
            "email"=>$email,
            "pay_id"=> $order_sn,
            "type"=>1,
            "price"=>$info['price'],
            "orderid"=>$info['id'],
            "ordersn"=>"",
            "ip"=>$ip,
            "createtime"=>time()
        ]);
        if ($res) {
            $codepay_id="592411";//这里改成码支付ID
            $codepay_key="sVy5ug1SIR4VkY5YSfNGVRuh96PEvlJS"; //这是您的通讯密钥

            $data = array(
                "id" => $codepay_id,//你的码支付ID
                "pay_id" => $order_sn, //唯一标识 可以是用户ID,用户名,session_id(),订单ID,ip 付款后返回
                "type" => 1,//1支付宝支付 3微信支付 2QQ钱包
                "price" => $info['price'],//金额100元
                "param" => "",//自定义参数
                "notify_url"=>"http://www.g-dang.com/api/Notify/getdata",//通知地址
                "return_url"=>"http://www.g-dang.com/api/Index/detail?id={$id}",//跳转地址
            ); //构造需要传递的参数

            ksort($data); //重新排序$data数组
            reset($data); //内部指针指向数组中的第一个元素

            $sign = ''; //初始化需要签名的字符为空
            $urls = ''; //初始化URL参数为空

            foreach ($data AS $key => $val) { //遍历需要传递的参数
                if ($val == '' || $key == 'sign')
                    continue; //跳过这些不参数签名
                if ($sign != '') { //后面追加&拼接URL
                    $sign .= "&";
                    $urls .= "&";
                }
                $sign .= "$key=$val"; //拼接为url参数形式
                $urls .= "$key=" . urlencode($val); //拼接为url参数形式并URL编码参数值
            }
            $query = $urls . '&sign=' . md5($sign . $codepay_key); //创建订单所需的参数
            $url = "http://api5.xiuxiu888.com/creat_order/?{$query}"; //支付页面
            return mz_apisuc("suc",array("url"=>$url));
        } else {
            return mz_apierror("订单创建失败");
        }
    }
    
    public function search() {
        if (request()->isPost()) {
            $email = input("email");
            $order = db("orders")->where("email='{$email}' and status=1")->select();
            if ($order) {
                $html = "<div>";
                foreach ($order as $k=>$v) {
                    $info = db("streetgirl")->where("id='{$v['orderid']}'")->find();
                    $html .= "<p style='color:red;'>{$info['title']}</p>";
                    $html .= "<p>{$info['hiden']}</p>";
                }
                $html .= "</div>";
                return mz_apisuc("成功",array('data'=>$html));
            } else {
                return mz_apierror("订单未找到");
            }
        }
        return $this->fetch("",[]);
    }
}