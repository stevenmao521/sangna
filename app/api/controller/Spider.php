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
        $url = input("url");
        \think\Loader::import('Simpledom.simple_html_dom');
        $html = file_get_html($url);
        
        $picsdata = [];
        $h4Elements = $html->find('.bunnypresslite_rpimg_in');
        foreach ($h4Elements as $k=>$v) {
            $img = $v->find("img");
            
            foreach ($img as $k1=>$v1) {
                #图片抓取
                $img_url = $v1->src;
                $save_dir = './public/uploads/down/';
                $res = mz_getImage($img_url, $save_dir);
                $img_return = "/uploads/down/".$res['file_name'];
                $picsdata[] = $img_return;
            }
        }
        
        
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
        
        $links = [];
        $detail = $html->find(".loopbox");
        $contents_data = [];
        $pics_data = [];
        $hrefs = [];
        foreach ($detail as $k => $v) {

            $a = $v->find("a");
            foreach ($a as $k1 => $v1) {
                $href = $v1->href;
                $hrefs[] = $href;
                #获取详情
                $html_2 = $href;
                $html_detail = file_get_html($html_2);
                $details = $html_detail->find('.post-content');
                foreach ($details as $k2 => $v2) {

                    $contents = "";
                    $content = $v2->find("<p>");
                    foreach ($content as $k3 => $v3) {
                        if ($k3 == 0) {
                            $links[] = htmlspecialchars($v3->innertext);
                        } else {
                            $contents .= $v3->innertext."</br>";
                        }
                    }
                    #图片
                    $pics = "";
//                    $figure = $v2->find("<figure>");
//                    foreach ($figure as $k3 => $v3) {
//                        if ($k3 <= 4) {
//                            $img = $v3->find("<img>");
//                            foreach ($img as $k4 => $v4) {
//                                #图片抓取 取 6张
//                                $img_url = $v4->src;
//                                $save_dir = './public/uploads/down/';
//                                $res = mz_getImage($img_url, $save_dir);
//                                $img_return = "/uploads/down/" . $res['file_name'];
//                                $pics .= $img_return . ";";
//                            }
//                        }
//                    }
                    $contents_data[] = $contents;
                    $pics_data[] = $pics;
                }
            }
        }

        foreach ($picsdata as $k=>$v) {
            $tmp = [];
            $tmp['title'] = $titles[$k];
            $tmp['details'] = mb_substr($contents_data[$k],0,15)."...";
            $tmp['hiden'] = $contents_data[$k];
            $tmp['pic'] = $v;
            $tmp['pics'] = $pics_data[$k];
            $tmp['mark'] = $links[$k];
            $tmp['createtime'] = time();
            $tmp['day'] = $dates[$k];
            $tmp['city'] = 1;
            $tmp['href'] = $hrefs[$k];
            $tmp['cates'] = 2;
            
            $tmp['price'] = 4.9;
            db("streetgirl")->insert($tmp);
        }
        echo "success";
        exit;
    }
    
    #系统补起多图
    public function getpics() {
        \think\Loader::import('Simpledom.simple_html_dom');
        $href = db("streetgirl")->where("istrash=0 and href is not null and pics is not null")->order("id desc")->find();
        print_r($href);exit;
        if ($href) {
            $url = $href['href'];
            $html_detail = file_get_html($url);
            $details = $html_detail->find('.post-content');
            foreach ($details as $k2 => $v2) {
                #图片
                $pics = "";
                $figure = $v2->find("<figure>");
                foreach ($figure as $k3 => $v3) {
                    
                    $img = $v3->find("<img>");
                    foreach ($img as $k4 => $v4) {
                        #图片抓取 取 6张
                        $img_url = $v4->src;
                        $save_dir = './public/uploads/down/';
                        $res = mz_getImage($img_url, $save_dir);
                        $img_return = "/uploads/down/" . $res['file_name'];
                        $pics .= $img_return . ";";
                    }
                   
                }
            }
            if ($pics) {
                db("streetgirl")->where("id='{$href['id']}'")->update([
                    "pics"=>$pics
                ]);
            } else {
                db("streetgirl")->where("id='{$href['id']}'")->update([
                    "pics"=>'nopic'
                ]);
            }
            
            echo "success".$href['id'];
        }
    }
    
}