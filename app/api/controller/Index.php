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

class Index extends Common{
    
    public function _initialize() {
        parent::_initialize();
    }
    
    public function index() {
        
        #幻灯
        $slide = db("streetgirl")->where("istrash=0 and isslide=1")->limit(4)->select();
        #热门
        $hot = db("streetgirl")->where("istrash=0 and ishot=1")->limit(4)->select();
        #zj
        $street = db("streetgirl")->where("istrash=0 and cates=1 and ishome=1")->limit(4)->select();
        #hs
        $hs = db("streetgirl")->where("istrash=0 and cates=2 and ishome=1")->limit(4)->select();
        
        return $this->fetch('',[
            'slide'=>$slide,
            'hot'=>$hot,
            'street'=>$street,
            'hs'=>$hs
        ]);
    }
    
    public function detail() {
        $id = input("id");
        $info = db("streetgirl")->where("id='{$id}'")->find();
        if ($info['pics']) {
            $pic = explode(";",substr($info['pics'],0,-1));
            $info['pics'] = $pic;
        }
        
        return $this->fetch('',['info'=>$info]);
    }
    
    public function lists() {
        $type = input("type");
        if ($type == 1) {
            $where = " cates=1 ";
        } elseif ($type == 2) {
            $where = " cates=2 ";
        }
        $lists = db("streetgirl")->where("istrash=0")->where($where)->select();
        
        return $this->fetch('',[
            "lists"=>$lists
        ]);
    }
    
}