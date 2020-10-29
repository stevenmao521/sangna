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

class Notify extends Common{
    public function getdata() {
        ksort($_POST); //排序post参数
        reset($_POST); //内部指针指向数组中的第一个元素
        $codepay_key="sVy5ug1SIR4VkY5YSfNGVRuh96PEvlJS"; //这是您的密钥
        $sign = '';//初始化
        foreach ($_POST AS $key => $val) { //遍历POST参数
            if ($val == '' || $key == 'sign') continue; //跳过这些不签名
            if ($sign) $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
            $sign .= "$key=$val"; //拼接为url参数形式
        }
        
        db("city")->insert([
            "createtime"=>time(),
            "name"=>$_POST['pay_no']?$_POST['pay_no']:"test",
        ]);
        
        if (!$_POST['pay_no'] || md5($sign . $codepay_key) != $_POST['sign']) { //不合法的数据
            exit('fail');  //返回失败 继续补单
        } else { //合法的数据
            //业务处理
            $pay_id = $_POST['pay_id']; //需要充值的ID 或订单号 或用户名
            $money = (float)$_POST['money']; //实际付款金额
            $price = (float)$_POST['price']; //订单的原价
            $param = $_POST['param']; //自定义参数
            $pay_no = $_POST['pay_no']; //流水号
            
            #查询订单
            $order = db("orders")->where("pay_id='{$_POST['pay_id']}'")->find();
            if (!$order || $order['status'] != 0) {
                exit('fail');  //返回失败 继续补单
            }
            db("orders")->where("id='{$order['id']}'")->update([
                "ordersn"=>$pay_no,
                "status"=>1,
                "paytime"=>time(),
                "money"=>$money
            ]);
            exit('success'); //返回成功 不要删除哦
        }
    }
}