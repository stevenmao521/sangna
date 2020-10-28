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
        #留言列表
        $comments = db("comments")->where("pid='{$id}' and status=1")->order("id desc")->limit(10)->select();
        
        if ($comments) {
            foreach ($comments as $k=>$v) {
                $comments[$k]['createtime'] = date('Y',$v['createtime'])."年".date('m',$v['createtime'])."月".date("d",$v['createtime'])."日"." ".date("H:i",$v['createtime']);
                if ($v['replytime']) {
                    $comments[$k]['replytime'] = date('Y',$v['replytime'])."年".date('m',$v['replytime'])."月".date("d",$v['replytime'])."日"." ".date("H:i",$v['replytime']);
                }
            }
        }
        return $this->fetch('',['info'=>$info,"id"=>$id, "comments"=>$comments]);
    }
    
    public function lists() {
        $type = input("type");
        $where = " istrash=0 ";
        if ($type == 1) {
            $where .= " and cates=1 ";
        } elseif ($type == 2) {
            $where .= " and cates=2 ";
        }
        $citys = input("city");
        $regions = input("region");
        $newers = input("newer");
        
        $choose = [];
        if ($citys) {
            $where .= " and city='{$citys}' ";
            $choose['city'] = $citys;
        }
        if ($regions) {
            $where .= " and region='{$regions}' ";
            $choose['region'] = $regions;
        }
        if ($newers) {
            $where .= " and newer='{$newers}' ";
            $choose['newer'] = $newers;
        }
        
        #城市
        $page = input('page') ? input('page') : 1;
        $city = db("city")->where("istrash=0")->select();
        $lists = db("streetgirl")->where($where)->paginate(1, false,['query'=>request()->param()]);
        $page = $lists->render();
        $lists = $lists->toArray();
        
        $newer = db("newer")->where("istrash=0")->select();
        
        foreach ($lists['data'] as $k=>$v) {
            $lists['data'][$k]['newer'] = db("newer")->where("id='{$v['newer']}'")->value("name");
        }
        return $this->fetch('',[
            "lists"=>$lists['data'],
            "city"=>$city,
            "newer"=>$newer,
            "type"=>$type,
            "choose"=>$choose,
            'page'=>$page
        ]);
    }
    
    public function getregion() {
        $pid = input("pid");
        $region = db("regions")->where("pid='{$pid}' and istrash=0")->field("id,name")->select();
        return mz_apisuc("suc",$region);
    }
    
}