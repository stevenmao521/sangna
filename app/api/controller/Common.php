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
                $this->error("网站维护中");
            }
        }
        if (!$has_cookie) {
            #根据ip注册为新用户
            $city = getCitynew();
            if ($city) {
                $cname = $city['cname'];
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
        $this->assign('title',$title);
    }
    
    public function _empty(){
        return $this->error('空操作，返回上次访问页面中...');
    }
}