<?php
namespace app\admin\controller;
use think\Controller;
use app\admin\model\Admin;
class Login extends Controller
{
    public function _initialize(){
        if (session('aid')) {
            $this->redirect('index/index');
        }
//        $ip = getIp();
//        if (!in_array($ip, [ '104.160.43.105','144.34.189.154','199.193.127.187','185.212.57.165','104.193.8.196'])) {
//            echo "404";
//            exit;
//        }
    }
    private $cache_model,$system;
    public function index(){
		if(request()->isPost()) {
            $admin = new Admin();
            $data = input('post.');
            if(!$this->check($data['captcha'])){
                return json(array('code' => 0, 'msg' => '验证码错误'));
            }
            $num = $admin->login($data);
            if($num == 1){
                return json(['code' => 1, 'msg' => '登录成功!', 'url' => url('index/index')]);
            }else {
                return json(array('code' => 0, 'msg' => '用户名或者密码错误，重新输入!'));
            }
        }else{
            $this->cache_model=array('Module','Role','Category','Posid','Field','System');
            $this->system = F('System');
            if(empty($this->system)){
                foreach($this->cache_model as $r){
                    savecache($r);
                }
            }
            return $this->fetch();
        }
    }
    public function check($code){
       return captcha_check($code);
    }
}