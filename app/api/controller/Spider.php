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
        set_time_limit(0);

        \think\Loader::import('Simpledom.simple_html_dom');
        $html = file_get_html("http://www.315lz.com");
        
//        $pics = [];
//        $h4Elements = $html->find('.bunnypresslite_rpimg_in');
//        foreach ($h4Elements as $k=>$v) {
//            $img = $v->find("img");
//            
//            foreach ($img as $k1=>$v1) {
//                #图片抓取
//                $img_url = $v1->src;
//                $save_dir = './public/uploads/down/';
//                $res = mz_getImage($img_url, $save_dir);
//                $img_return = "/uploads/down/".$res['file_name'];
//                $pics[] = $img_return;
//            }
//        }
        
        #标题抓取
        $titles = [];
        $title = $html->find('.listpage_item_title');
        foreach ($title as $k=>$v) {
            $h2 = $v->find("<h2>");
            foreach ($h2 as $k1=>$v1) {
                $titles[] = $v1->innertext;
            }
        }
        
        #日期
        $dates = [];
        $date = $html->find('.post-date');
        foreach ($date as $k=>$v) {
            $dates[] = $v->innertext;
        }
        
//        $ins_data = [];
//        foreach ($pics as $k=>$v) {
//            $tmp = [];
//            $tmp['pic'] = $v;
//            $tmp['title'] = $titles[$k];
//            $tmp['date'] = $dates[$k];
//            $ins_data[] = $tmp;
//        }
        
        
        $detail = $html->find(".loopbox");
        foreach ($detail as $k=>$v) {
            $a = $v->find("a");
            foreach ($a as $k1=>$v1) {
                $href = $v1->href;
                
                if ($k1 == 0) {
                    #获取详情
                    $html_2 = $href;
                    $html_detail = file_get_html($html_2);
                    $details = $html_detail->find('.post-content');
                    foreach ($details as $k2=>$v2) {
                        echo $v->innertext;
                    }
                    
                }
            }
        }
        
        exit;
        
        
        $content = "";
        foreach ($detail as $k=>$v) {
            echo $v->innertext;
        }
        echo $content;
        exit;
        
        return $this->fetch("",[]);
    }
    
}