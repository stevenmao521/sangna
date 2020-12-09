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
        $html = file_get_html("http://www.315lz.com");
        
        $ins_data = [];
        
        $h4Elements = $html->find('.bunnypresslite_rpimg_in');
        foreach ($h4Elements as $k=>$v) {
            $img = $v->find("img");
            foreach ($img as $k1=>$v1) {
                #图片抓取
                echo $v1->src;
            }
        }
        
        #标题抓取
        $title = $html->find('.listpage_item_title');
        foreach ($title as $k=>$v) {
            
            $h2 = $v->find("<h2>");
            foreach ($h2 as $k1=>$v1) {
                echo $v1->innertext;
            }
        }
        
        #日期
        $date = $html->find('.post-date');
        foreach ($date as $k=>$v) {
            echo $v->innertext;
        }
        
        #获取详情
        $html_2 = "http://www.315lz.com/7724.html";
        $html_detail = file_get_html($html_2);
        
        $detail = $html_detail->find('.post-content');
        
        $content = "";
        foreach ($detail as $k=>$v) {
            echo $v->innertext;
        }
        echo $content;
        exit;
        
        return $this->fetch("",[]);
    }
    
}