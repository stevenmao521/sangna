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

class Comments extends Common{
    
    public function _initialize() {
        parent::_initialize();
    }
    
    public function done() {
        $nickname = input("nickname");
        $contents = input("contents");
        $ip = getIp();
        $id = input("id");
        
        $has = db("comments")->where("ip='{$ip}'")->order("id desc")->find();
        if ($has) {
            $diff_time = time() - $has['createtime'];
            if ($diff_time<60) {
                return mz_apierror("留言太快，请稍后再试");
            } else {
                db("comments")->insert(array(
                    "pid"=>$id,
                    "nickname"=>$nickname,
                    "contents"=>$contents,
                    "ip"=>$ip,
                    "status"=>0,
                    "createtime"=>time()
                ));
            }
        } else {
            db("comments")->insert(array(
                "pid"=>$id,
                "nickname"=>$nickname,
                "contents"=>$contents,
                "ip"=>$ip,
                "status"=>0,
                "createtime"=>time()
            ));
        }
        return mz_apisuc("留言成功,管理员审核");
    }
    
}