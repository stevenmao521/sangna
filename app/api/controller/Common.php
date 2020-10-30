<?php
namespace app\api\controller;
use think\Input;
use think\Db;
use clt\Leftnav;
use think\Request;
use think\Controller;
use think\Cookie;

class Common extends Controller{
    protected $pagesize;
    public function _initialize(){
        $sys = F('System');
        $this->assign('sys',$sys);
        //获取控制方法
        $request = Request::instance();
        
        //print_r($request);
        $action = $request->action();
        $controller = $request->controller();
        $this->assign('action',($action));
        $this->assign('controller',strtolower($controller));
        define('MODULE_NAME',strtolower($controller));
        define('ACTION_NAME',strtolower($action));
        
        $title = [];
        $title['zj'] = db("cates")->where("id=1")->value("name");
        $title['lf'] = db("cates")->where("id=3")->value("name");
        $title['hs'] = db("cates")->where("id=2")->value("name");
        
        #进入首页获取IP信息并保存
        $ip = getIp();
        $has_cookie = session($ip);
        $user = db("tuser")->where("ip='{$ip}'")->find();
        if ($user) {
            if ($user['forbid'] == 1) {
                echo 404;
                exit;
            }
        }
        
        if (!$has_cookie) {
            
            #$ip = "186.226.69.114";
            $city = getCitynew($ip);
            if ($city) {
                if ($city["data"][0] == "中国") {
                    if ($user) {
                        db("tuser")->where("id='{$tuser['id']}'")->update(["forbid"=>1]);
                    } else {
                        db("tuser")->insertGetId([
                            "ip"=>$ip,
                            "lastlogin"=>time(),
                            "createtime"=>time(),
                            "cname"=>$city["data"][0].$city["data"][1].$city["data"][2],
                            "forbid"=>1
                        ]);
                    }
                    echo 404;
                    exit;
                }
            }
            
            #根据ip注册为新用户
            if ($city) {
                $cname = $city["data"][0].$city["data"][1].$city["data"][2];
            } else {
                $cname = "";
            }
            $user = db("tuser")->where("ip='{$ip}'")->find();
            if ($user) {
                db("tuser")->where("ip='{$ip}'")->update(['lastlogin'=>time()]);
                $tuser_id = $user['id'];
            } else {
                
                $tuser_id = db("tuser")->insertGetId([
                    "ip"=>$ip,
                    "lastlogin"=>time(),
                    "createtime"=>time(),
                    "cname"=>$cname,
                ]);
            }
            session($ip,$tuser_id);
        }
        
        $cites = db("city")->where("istrash=0")->select();
        if (session("city")) {
            $cite = session("city");
            $def_city = db("city")->where("id='{$cite}'")->value("name");
        } else {
            $def_city = "深圳市";
            session("city", 1);
        }
        
        $this->assign("city",$cites);
        $this->assign("def_city",$def_city);
        $this->assign('title',$title);
    }
    
    public function _empty(){
        return $this->error('空操作，返回上次访问页面中...');
    }
}