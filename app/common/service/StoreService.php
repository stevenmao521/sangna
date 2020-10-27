<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-06-04
 * Time: 11:19
 */

namespace app\common\service;
use think\Db;
use app\common\tool\Smscode;

class StoreService
{
    public function checkStore($orders) {
        if ($orders) {
            $product_ids = [];
            foreach ($orders as $k=>$order) {
                $product = db("product")->where("id='{$order['pid']}'")->find();
                if ($product['issuit'] == 1) {
                    #更新库存
                    $suit = db("sale_suit")->where("saleid='{$order['id']}'")->select();
                    foreach ($suit as $k1=>$v1) {
                        $product_ids[] = $v1['pid'];
                    }
                } else {
                    $product_ids[] = $order['pid'];
                }
            }
            $product_ids = array_unique($product_ids);
            if ($product_ids) {
                $product_ids_str = implode(",", $product_ids);
                $store = db("store")->where("pid IN ({$product_ids_str})")->select();
                if ($store) {
                    $report = [];
                    foreach ($store as $k=>$v) {
                        if ($v['nums'] < $v['cq_report']) {
                            $report[] = $v['id'];
                        }
                        if ($v['nums_handle'] < $v['handle_report']) {
                            $report[] = $v['id'];
                        }
                    }
                }
                $report = array_unique($report);
                if ($report) {
                    $content = "";
                    foreach ($report as $k=>$v) {
                        $content .= $v.",";
                    }
                    #echo $content;exit;
                    $sms = new Smscode();
                    $sms->send_code('17749977741,19923095243,17300288431', "{$content}");
                }
            }
            
        }
    }
}
