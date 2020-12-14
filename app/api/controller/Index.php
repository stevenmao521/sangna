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
    
    #登录
    public function login() {
        $name = mz_filter(input("username"));
        $password = mz_filter(input("password"));
        $ips = getIp();
        $tuser = db("tuser")->where("username='{$name}'")->find();
        
        if (strlen($name) > 20) {
            $this->error("用户名过长");
        }
        
        if (!$name) {
            $this->error("请填写用户名");
        }
        
        if (!$tuser) {
            #ip查看
            $ip = db("tuser")->where("ip='{$ips}'")->order("id desc")->find();
            
            if($ip) {
                $diff = time() - $ip['createtime'];
                if ($diff < 3600) {
                    $this->error("请勿频繁操作");
                }
            }
            
            #注册
            $tuser_id = db("tuser")->insertGetId([
                "ip"=>$ips,
                "lastlogin"=>time(),
                "createtime"=>time(),
                "username"=>$name,
                "password"=>md5($password)
            ]);
            cookie("uid", $tuser_id, 3600*24*7);
            $this->success("注册成功");
        } else {
            if (md5($password) == $tuser['password']) {
                #成功
                cookie("uid", $tuser['id'], 3600*24*7);
                db("tuser")->where("id='{$tuser['id']}'")->update([
                    "lastlogin"=>time()
                ]);
                
                $this->success("登录成功");
            } else {
                $this->error("密码错误");
            }
        }
    }
    
    public function index() {
        $city = session("city");
        
        if ($city > 0) {
            $whereother = " and city = '{$city}' ";
        } else {
            $whereother = "";
        }
        
        #过去一周
        $day_last = strtotime("-7 days",time());
        #幻灯
        $slide = db("streetgirl")->where("istrash=0 {$whereother}")->order("day_time desc")->limit(4)->select();
        #热门
        #$hot = db("streetgirl")->where("istrash=0 and ishot=1  {$whereother}")->limit(4)->select();
        $hot = db("streetgirl")->where("istrash=0 {$whereother} and createtime>='{$day_last}'")->order("hot desc")->limit(6)->select();
        #zj
        #$street = db("streetgirl")->where("istrash=0 and cates=1 and ishome=1  {$whereother}")->limit(4)->select();
        $street = db("streetgirl")->where("istrash=0 and cates=1 {$whereother}")->order("day_time desc")->limit(4)->select();
        #hs
        #$hs = db("streetgirl")->where("istrash=0 and cates=2 and ishome=1  {$whereother}")->limit(4)->select();
        $hs = db("streetgirl")->where("istrash=0 and cates=2 {$whereother}")->order("day_time desc")->limit(10)->select();
        #hs
        #$lf = db("streetgirl")->where("istrash=0 and cates=3 and ishome=1  {$whereother}")->limit(4)->select();
        $lf = db("streetgirl")->where("istrash=0 and cates=3  {$whereother}")->order("day_time desc")->limit(4)->select();
        
        $title = [];
        $title['zj'] = db("cates")->where("id=1")->value("name");
        $title['lf'] = db("cates")->where("id=3")->value("name");
        $title['hs'] = db("cates")->where("id=2")->value("name");
        return $this->fetch('',[
            'slide'=>$slide,
            'hot'=>$hot,
            'street'=>$street,
            'hs'=>$hs,
            'lf'=>$lf,
            'title'=>$title
        ]);
    }
    
    public function detail() {
        $id = input("id");
        $info = db("streetgirl")->where("id='{$id}'")->find();
        $ip = getIp();
        $tuserId = session($ip);
        
        if ($info['pics']) {
            $pic = explode(";",substr($info['pics'],0,-1));
            $info['pics'] = $pic;
        }
        
        db("streetgirl")->where("id='{$id}'")->setInc("hot");
        
        #是否显示隐藏
        $order = db("orders")->where("orderid='{$id}' and uid='{$tuserId}' and status=1")->find();
        #$info['details'] = mb_substr($info['hiden'], 0, 50);
        if ($order && $order['status'] == 1) {
            $info['showhidden'] = $info['hiden'];
            $info['haspay'] = 1;
        } else {
            $info['showhidden'] = $info['hiden'];
            #$info['showhidden'] = "留言后后显示";
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
        } elseif ($type == 3) {
            $where .= " and cates=3 ";
        }
        $citys = input("city");
        $regions = input("region");
        $newers = input("newer");
        
        $choose = [];
        if ($citys) {
            $where .= " and city='{$citys}' ";
            $choose['city'] = $citys;
        } else {
            $cite = session("city");
            if ($cite > 0) {
                $where .= " and city='{$cite}' ";
                $choose['city'] = $cite;
            }
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
        $lists = db("streetgirl")->where($where)->order("day_time desc")->paginate(10, false,['query'=>request()->param()]);
        $page = $lists->render();
        $lists = $lists->toArray();
        
        $newer = db("newer")->where("istrash=0")->select();
        
        foreach ($lists['data'] as $k=>$v) {
            $lists['data'][$k]['newer'] = db("newer")->where("id='{$v['newer']}'")->value("name");
            $lists['data'][$k]['mark'] = mb_substr($v['mark'],0,30)."...";
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