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

class Spider extends Common{
    
    public function _initialize() {
        parent::_initialize();
    }
    
    public function index() {
        \think\Loader::import('Simpledom.simple_html_dom');
        $html = file_get_html("http://www.315lz.com/date/2020/12");
        $h4Elements = $html->find('<div class="bunnypresslite_rpimg_in">');
        foreach ($h4Elements as $k=>$v) {
            
            print_r(htmlspecialchars($v->innertext));
            
//            if (strpos($v->innertext,"<img width='391' height='400'") !== false) {
//                echo $v->innertext;
//            }
            
        }
        
        exit;
        
        return $this->fetch("",[]);
    }
    
}