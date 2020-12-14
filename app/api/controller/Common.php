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
        #获取cookie
        $uid = cookie("uid");
        
        $show_login = false;
        if (!$uid) {
            #弹出注册登录框
            $show_login = true;
        } else {
            $tuser = db("tuser")->where("id='{$uid}'")->find();
            db("tuser")->where("id='{$tuser['id']}'")->update([
                "lastlogin"=>time()
            ]);
            cookie("username", $tuser['username'], 3600*24*7);
        }
        
        $cites = db("city")->where("istrash=0")->select();
        if (session("city")) {
            $cite = session("city");
            $def_city = db("city")->where("id='{$cite}'")->value("name");
        } else {
            $def_city = "全部";
            session("city", 0);
        }
        
        $this->assign("username", cookie("username"));
        $this->assign("showlogin", $show_login);
        $this->assign("city",$cites);
        $this->assign("def_city",$def_city);
        $this->assign('title',$title);
    }
    
    public function _empty(){
        return $this->error('空操作，返回上次访问页面中...');
    }
}