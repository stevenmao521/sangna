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
            
            $day = $dates[$k];
            $day = str_replace("年","-",$day);
            $day = str_replace("月","-",$day);
            $day = str_replace("日","",$day);
            $tmp['day_time'] = $day;
            
            db("streetgirl")->insert($tmp);
        }
        echo "success";
        exit;
    }
    
    #抓取评论
    public function getcomments() {
        \think\Loader::import('Simpledom.simple_html_dom');
        $href = db("streetgirl")->where("istrash=0 and href is not null and hasupdatecomments=0")->order("id desc")->find();
        
        if ($href) {
            $url = $href['href'];
            #$url = "http://www.315lz.com/7802.html";
            $html_detail = file_get_html($url);
            
            $comments = $html_detail->find(".comment-body");
            $comment = [];
            foreach ($comments as $k=>$v) {
                $ins = [];
                
                $author = $v->find(".comment-auther");
                $author_txt = $author[0]->innertext;
                $str= preg_replace('/<\s*img\s+[^>]*?src\s*=\s*(\'|\")(.*?)\\1[^>]*?\/?\s*>/i', '', $author_txt);
                
                $date = $v->find(".comment-date");
                $date_str = $date[0]->innertext;
                $year = mb_substr($date_str,0,4);
                $month = mb_substr($date_str,5,2);
                $day = mb_substr($date_str,8,2);
                $time = mb_substr($date_str,13,6);
                $af_time = $year."-".$month."-".$day." ".$time;
                $af_time = str_replace("日", "", $af_time);
                
                $text = $v->find(".comment-text");
                $text_str = $text[0]->innertext;
                
                $ins['nickname'] = $str;
                $ins['contents'] = $text_str;
                $ins['createtime'] = $af_time;
                $ins['status'] = 1;
                $ins['pid'] = $href['id'];
                $comment[] = $ins;
                
            }
            
            print_r($comment);exit;
            db("comments")->insertAll($comment);
            db("streetgirl")->where("id='{$href['id']}'")->update([
                "hasupdatecomments"=>1
            ]);
        } else {
            db("streetgirl")->where("id='{$href['id']}'")->update([
                "hasupdatecomments"=>1
            ]);
        }
    }
    
    #系统补起多图
    public function getpics() {
        \think\Loader::import('Simpledom.simple_html_dom');
        $href = db("streetgirl")->where("istrash=0 and href is not null and hasupdate=0")->order("id desc")->find();
        
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
                    "pics"=>$pics,
                    "hasupdate"=>1
                ]);
            } else {
                db("streetgirl")->where("id='{$href['id']}'")->update([
                    "pics"=>'nopic',
                    "hasupdate"=>1
                ]);
            }
            
            echo "success".$href['id'];
        }
    }
    
}