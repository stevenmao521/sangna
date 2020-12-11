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
use think\Db;


class System extends Common{
    
    public function _initialize() {
        parent::_initialize();
    }
    
    public function daoru() {
        return $this->fetch('daoru',[]);
    }
    
    #更新时间
    public function updatetime() {
        $list = db("streetgirl")->where("istrash=0 and day!= '' ")->select();
        foreach ($list as $k=>$v) {
            $day = $v['day'];
            $day = str_replace("年","-",$day);
            $day = str_replace("月","-",$day);
            $day = str_replace("日","",$day);
            db("streetgirl")->where("id='{$v['id']}'")->update(["day_time"=>$day]);
        }
        
        $list = db("streetgirl")->where("istrash=0 and day= '' ")->select();
        foreach ($list as $k=>$v) {
            db("streetgirl")->where("id='{$v['id']}'")->update(["day_time"=>date("Y-m-d",$v['createtime'])]);
        }
    }
    
    #导入销售表
    public function daorup() {
        set_time_limit(0);
        $res = $this->upload();
        $date = date('Ymd');
        $filePath = ROOT_PATH."public/uploads/{$date}/".$res;
        
        import('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                $this->error('no Excel');
            }
        }
        $PHPExcel = $PHPReader->load($filePath); 
        $s = $PHPExcel->getSheet(0)->toArray(null,true,true,true);
        
        foreach ($s as $k => $v) {
            if ($k == 1) {
                continue;
            } else {
                $ins_data = [];
                $ins_data['title'] = $v['A'];
                $ins_data['city'] = db("city")->where("name='{$v['B']}'")->value("id");
                $ins_data['region'] = db("regions")->where("name='{$v['C']}'")->value("id");
                
                if ($v['D'] == 'ZJ') {
                    $ins_data['cates'] = 1;
                }elseif($v['D'] == 'LF') {
                    $ins_data['cates'] = 3;
                }elseif($v['D'] == 'HS') {
                    $ins_data['cates'] = 2;
                }
                $ins_data['price'] = $v['E'];
                $ins_data['mark'] = $v['F'];
                $ins_data['details'] = $v['G'];
                $ins_data['hidden'] = $v['H'];
                
                $ins_data['createtime'] = time();
                db("streetgirl")->insert($ins_data);
            }
        }
        $this->success("导入成功");
    }
    
    //文件上传提交 
    public function upload() {
        //获取表单上传文件 
        $file = request()->file('files');
        if (empty($file)) {
            $this->error('请选择上传文件');
        }
        //移动到框架应用根目录/public/uploads/ 目录下 
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if ($info) {
            //$this->success('文件上传成功');
            return $info->getFilename();
        } else {
            //上传失败获取错误信息 
            $this->error($file->getError());
        }
    }
}