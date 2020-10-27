<?php
/*
 * 公共工具类
 */
namespace app\xq\controller;
use think\Db;
use think\Request;
use think\Controller;
use think\Session;
use clt\FormSel;    #表单
use app\xq\controller\Helper as Helper;//工具类

class Export extends Common{
    
    protected $field_mod;#字段模型
    protected $form;
    protected $log_mod;#日志模型
    protected $module_mod;#模型
    protected $helper;  #工具

    #初始化
    public function _initialize() {
        parent::_initialize();
        $this->field_mod = db('field'); #字段模型
        $this->log_mod = db('logs');
        $this->module_mod = db('module');
        $this->form = new FormSel(); #表单
        
        $this->helper = new Helper();
    }
    
    public function index() {
        #来自跳转
        $from = input("from");
        $sess = session('export_sess');
        
        if ($sess && $from==1) {
            #获取导出列表字段
            $modid = $sess['model_id'];
            $sel_map = $sess['sel_map'];
            $fields = $this->helper->getLfield($modid);#列表字段
            if ($fields) {
                $fields_str = "";
                $f_arr = array();
                foreach ($fields as $k=>$v) {
                    $f_arr[] = $v['name'];
                    $fields_str .= $k.",";
                }
                $fields_str = rtrim($fields_str,',');
            }
            #模型
            $mod_info = $this->module_mod->where("id='{$modid}'")->find();
            #列表
            $list = db($mod_info['name'])->where($sel_map)->where("istrash=0")->field($fields_str)->select();
            
            
            #删除session
            Session::delete('export_sess');
            \think\Loader::import('PHPExcel.PHPExcel');
            \think\Loader::import('PHPExcel.IOFactory.PHPExcel_IOFactory');
            $objPHPExcel = new \PHPExcel();
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            // Set document properties
            $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                    ->setLastModifiedBy("Maarten Balliauw")
                    ->setTitle("Office 2007 XLSX Test Document")
                    ->setSubject("Office 2007 XLSX Test Document")
                    ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                    ->setKeywords("office 2007 openxml php")
                    ->setCategory("Test result file");

            $xls_arr = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ");
            if ($f_arr) {
                foreach ($f_arr as $k => $v) {
                    $tag = "";
                    //查询当前表该字段的格式
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$k] . '1', $v);
                    $objPHPExcel->getActiveSheet()->getColumnDimension($xls_arr[$k])->setWidth(24);
                    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(24);
                    #居中
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
            }
            $tag = 2;
            if ($list) {
                
                foreach ($list as $k=>$v) {
                    $i=0;
                    foreach ($v as $k1=>$v1) {
                        
                        #字段过滤
                        foreach ($fields as $k2=>$v2) {
                            if ($k2 == $k1) {
                                if ($v2['type'] == 'select' || $v2['type'] == 'radio') {
                                    #下拉选项
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $options = $v2['setup']['options'];
                                    $options = explode("\n",$v2['setup']['options']);
                                    foreach($options as $r) {
                                        $v3 = explode("|",$r);
                                        $k3 = trim($v3[1]);
                                        if ($k3 == $v1) {
                                            $v1 = $v3[0];
                                        }
                                    }
                                } elseif ($v2['type'] == 'fkey') {
                                    #外键
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $v1 = db($v2['setup']['modname'])->where("id='{$v1}'")->value($v2['setup']['keyname']);
                                } elseif ($v2['type'] == 'datetime') {
                                    if ($v1) {
                                        $v1 = date('Y-m-d', $v1);
                                    } else {
                                        $v1 = "";
                                    }
                                }
                            }
                        }
                        #$v1 = "\t".$v1."\t";
                        //$objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.$j,$result[1],PHPExcel_Cell_DataType::TYPE_STRING);
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$i] . $tag, $v1);
                        $objPHPExcel->getActiveSheet()->getRowDimension($tag)->setRowHeight(24);
                        $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $i++;
                    }
                    $tag++;
                }
            }
            
            // Rename worksheet
            $objPHPExcel->getActiveSheet()->setTitle("{$mod_info['title']}");
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $objPHPExcel->setActiveSheetIndex(0);
            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $mod_info['title'] . '导出文件.xls"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter->save('php://output');
            exit;
        } else {
            $post = input("post.");
            $modid = $post['id'];
            $sel_map = $this->helper->getMap($post, $modid);
            #存入session
            $rs = array();
            $rs['model_id'] = $modid;
            $rs['sel_map'] = $sel_map;
            Session::set('export_sess', $rs); 
            exit;
        }
        
    }
    
    public function purchaseorder() {
        #来自跳转
        $from = input("from");
        $sess = session('export_sess');
        
        if ($sess && $from==1) {
            #获取导出列表字段
            $modid = $sess['model_id'];
            $sel_map = $sess['sel_map'];
            $fields = $this->helper->getLfield($modid);#列表字段
            if ($fields) {
                $fields_str = "";
                $f_arr = array();
                foreach ($fields as $k=>$v) {
                    $f_arr[] = $v['name'];
                    $fields_str .= $k.",";
                }
                $fields_str = rtrim($fields_str,',');
            }
            $fields_str .= ",collate_product,collate_trans,fengcun,collate_handle";
            
            #模型
            $mod_info = $this->module_mod->where("id='{$modid}'")->find();
            #列表
            $list = db($mod_info['name'])->where($sel_map)->where("istrash=0 and hasstore=2")->field($fields_str)->select();
            
            
            #删除session
            Session::delete('export_sess');
            \think\Loader::import('PHPExcel.PHPExcel');
            \think\Loader::import('PHPExcel.IOFactory.PHPExcel_IOFactory');
            $objPHPExcel = new \PHPExcel();
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            // Set document properties
            $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                    ->setLastModifiedBy("Maarten Balliauw")
                    ->setTitle("Office 2007 XLSX Test Document")
                    ->setSubject("Office 2007 XLSX Test Document")
                    ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                    ->setKeywords("office 2007 openxml php")
                    ->setCategory("Test result file");

            $xls_arr = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ");
            if ($f_arr) {
                foreach ($f_arr as $k => $v) {
                    $tag = "";
                    //查询当前表该字段的格式
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$k] . '1', $v);
                    $objPHPExcel->getActiveSheet()->getColumnDimension($xls_arr[$k])->setWidth(24);
                    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(24);
                    #居中
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
            }
            $tag = 2;
            
            
            if ($list) {
                
                foreach ($list as $k=>$v) {
                    if ($v['fengcun'] == 1) {
                        $list[$k]['suc'] = 3; 
                    } else {
                        if ($v['collate_transe'] == 2 || $v['collate_product'] == 2 || $v['collate_handle'] == 2) {
                            $list[$k]['suc'] = 1; 
                            continue;
                        } elseif ($v['collate_product'] == 1 && $v['collate_transe'] == 0) {
                            $list[$k]['suc'] = 2; 
                            continue;
                        } elseif ($v['collate_product'] == 1 && $v['collate_transe'] == 1) {
                            $list[$k]['suc'] = 2; 
                            continue;
                        } else {
                            $list[$k]['suc'] = 0; 
                        }
                    }
                }
                
                foreach ($list as $k=>$v) {
                    $i=0;
                    foreach ($v as $k1=>$v1) {
                        #字段过滤
                        foreach ($fields as $k2=>$v2) {
                            if ($k2 == $k1) {
                                if ($v2['type'] == 'select' || $v2['type'] == 'radio') {
                                    #下拉选项
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $options = $v2['setup']['options'];
                                    $options = explode("\n",$v2['setup']['options']);
                                    foreach($options as $r) {
                                        $v3 = explode("|",$r);
                                        $k3 = trim($v3[1]);
                                        if ($k3 == $v1) {
                                            $v1 = $v3[0];
                                        }
                                    }
                                } elseif ($v2['type'] == 'fkey') {
                                    #外键
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $v1 = db($v2['setup']['modname'])->where("id='{$v1}'")->value($v2['setup']['keyname']);
                                } elseif ($v2['type'] == 'datetime') {
                                    if ($v1) {
                                        $v1 = date('Y-m-d', $v1);
                                    } else {
                                        $v1 = "";
                                    }
                                }
                            }
                        }
                        #$v1 = "\t".$v1."\t";
                        
                        switch($v['suc']) {
                            case 0:
                                break;
                            case 1:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF6666');
                                break;
                            case 2:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCFFCC');
                                break;
                            case 3:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
                                break;
                        }
                        
                        //$objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.$j,$result[1],PHPExcel_Cell_DataType::TYPE_STRING);
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$i] . $tag, $v1);
                        $objPHPExcel->getActiveSheet()->getRowDimension($tag)->setRowHeight(24);
                        $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $i++;
                    }
                    $tag++;
                }
            }
            
            // Rename worksheet
            $objPHPExcel->getActiveSheet()->setTitle("{$mod_info['title']}");
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $objPHPExcel->setActiveSheetIndex(0);
            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $mod_info['title'] . '导出文件.xls"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter->save('php://output');
            exit;
        } else {
            $post = input("post.");
            $modid = $post['id'];
            $sel_map = $this->helper->getMap($post, $modid);
            #存入session
            $rs = array();
            $rs['model_id'] = $modid;
            $rs['sel_map'] = $sel_map;
            Session::set('export_sess', $rs); 
            exit;
        }
    }
    
    public function sale() {
        set_time_limit(0);
        #来自跳转
        $from = input("from");
        $sess = session('export_sess');
        
        if ($sess && $from==1) {
            #获取导出列表字段
            $modid = $sess['model_id'];
            $sel_map = $sess['sel_map'];
            $fields = $this->helper->getLfield($modid);#列表字段
            if ($fields) {
                $fields_str = "";
                $f_arr = array();
                foreach ($fields as $k=>$v) {
                    $f_arr[] = $v['name'];
                    $fields_str .= $k.",";
                }
                $fields_str = rtrim($fields_str,',');
            }
            $fields_str .= ",collate_product,collate_trans,fengcun,collate_productout";
            
            #模型
            $mod_info = $this->module_mod->where("id='{$modid}'")->find();
            #列表
            $list = db($mod_info['name'])->where($sel_map)->where("istrash=0 and hasout=2")->field($fields_str)->select();
            
            
            #删除session
            Session::delete('export_sess');
            \think\Loader::import('PHPExcel.PHPExcel');
            \think\Loader::import('PHPExcel.IOFactory.PHPExcel_IOFactory');
            $objPHPExcel = new \PHPExcel();
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            // Set document properties
            $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                    ->setLastModifiedBy("Maarten Balliauw")
                    ->setTitle("Office 2007 XLSX Test Document")
                    ->setSubject("Office 2007 XLSX Test Document")
                    ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                    ->setKeywords("office 2007 openxml php")
                    ->setCategory("Test result file");

            $xls_arr = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ");
            if ($f_arr) {
                foreach ($f_arr as $k => $v) {
                    $tag = "";
                    //查询当前表该字段的格式
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$k] . '1', $v);
                    $objPHPExcel->getActiveSheet()->getColumnDimension($xls_arr[$k])->setWidth(24);
                    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(24);
                    #居中
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
            }
            $tag = 2;
            
            
            if ($list) {
                
                foreach ($list as $k=>$v) {
                    if ($v['fengcun'] == 1) {
                        $list[$k]['suc'] = 3; 
                    } else {
                        //重庆发货
                        if ($v['storage'] == 1) {
                            if ($v['collate_trans'] == 2 || $v['collate_product'] == 2) {
                                $list[$k]['suc'] = 1; 
                            } else if ($v['collate_product'] == 1 && $v['collate_trans'] == 0) {
                                $list[$k]['suc'] = 2; 
                            } else if ($v['collate_product'] == 1 && $v['collate_trans'] == 1) {
                                $list[$k]['suc'] = 2; 
                            }
                        } else if($v['storage'] == 2) {
                            if ($v['collate_product'] == 0 && $v['collate_trans'] == 0 && $v['collate_productout'] == 0) {

                            } else {
                                if ($v['collate_product'] == 2 || $v['collate_trans'] == 2 || $v['collate_productout'] == 2) {
                                    $list[$k]['suc'] = 1;
                                } else if ($v['collate_product'] == 1 && $v['collate_trans'] == 0 && $v['collate_productout'] == 1) {
                                    $list[$k]['suc'] = 2;
                                } else if ($v['collate_product'] == 1 && $v['collate_trans'] == 1 && $v['collate_productout'] == 1) {
                                    $list[$k]['suc'] = 2;
                                } else {
                                    $list[$k]['suc'] = 1;
                                }
                            }
                        }
                    }
                }
                
                foreach ($list as $k=>$v) {
                    $i=0;
                    foreach ($v as $k1=>$v1) {
                        #字段过滤
                        foreach ($fields as $k2=>$v2) {
                            if ($k2 == $k1) {
                                if ($v2['type'] == 'select' || $v2['type'] == 'radio') {
                                    #下拉选项
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $options = $v2['setup']['options'];
                                    $options = explode("\n",$v2['setup']['options']);
                                    foreach($options as $r) {
                                        $v3 = explode("|",$r);
                                        $k3 = trim($v3[1]);
                                        if ($k3 == $v1) {
                                            $v1 = $v3[0];
                                        }
                                    }
                                } elseif ($v2['type'] == 'fkey') {
                                    #外键
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $v1 = db($v2['setup']['modname'])->where("id='{$v1}'")->value($v2['setup']['keyname']);
                                } elseif ($v2['type'] == 'datetime') {
                                    if ($v1) {
                                        $v1 = date('Y-m-d', $v1);
                                    } else {
                                        $v1 = "";
                                    }
                                }
                            }
                        }
                        #$v1 = "\t".$v1."\t";
                        
                        switch($v['suc']) {
                            case 0:
                                break;
                            case 1:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF6666');
                                break;
                            case 2:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCFFCC');
                                break;
                            case 3:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
                                break;
                        }
                        
                        //$objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.$j,$result[1],PHPExcel_Cell_DataType::TYPE_STRING);
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$i] . $tag, $v1);
                        $objPHPExcel->getActiveSheet()->getRowDimension($tag)->setRowHeight(24);
                        $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $i++;
                    }
                    $tag++;
                }
            }
            
            // Rename worksheet
            $objPHPExcel->getActiveSheet()->setTitle("{$mod_info['title']}");
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $objPHPExcel->setActiveSheetIndex(0);
            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $mod_info['title'] . '导出文件.xls"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter->save('php://output');
            exit;
        } else {
            $post = input("post.");
            $modid = $post['id'];
            $sel_map = $this->helper->getMap($post, $modid);
            #存入session
            $rs = array();
            $rs['model_id'] = $modid;
            $rs['sel_map'] = $sel_map;
            Session::set('export_sess', $rs); 
            exit;
        }
    }
    
    public function farm() {
        #来自跳转
        $from = input("from");
        $sess = session('export_sess');
        
        if ($sess && $from==1) {
            #获取导出列表字段
            $modid = $sess['model_id'];
            $sel_map = $sess['sel_map'];
            $fields = $this->helper->getLfield($modid);#列表字段
            if ($fields) {
                $fields_str = "";
                $f_arr = array();
                foreach ($fields as $k=>$v) {
                    $f_arr[] = $v['name'];
                    $fields_str .= $k.",";
                }
                $fields_str = rtrim($fields_str,',');
            }
            $fields_str .= ",collate_product,collate_productout,collate_reward,fengcun";
            
            #模型
            $mod_info = $this->module_mod->where("id='{$modid}'")->find();
            #列表
            $list = db($mod_info['name'])->where($sel_map)->where("istrash=0")->field($fields_str)->select();
            
            
            #删除session
            Session::delete('export_sess');
            \think\Loader::import('PHPExcel.PHPExcel');
            \think\Loader::import('PHPExcel.IOFactory.PHPExcel_IOFactory');
            $objPHPExcel = new \PHPExcel();
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            // Set document properties
            $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                    ->setLastModifiedBy("Maarten Balliauw")
                    ->setTitle("Office 2007 XLSX Test Document")
                    ->setSubject("Office 2007 XLSX Test Document")
                    ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                    ->setKeywords("office 2007 openxml php")
                    ->setCategory("Test result file");

            $xls_arr = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ");
            if ($f_arr) {
                foreach ($f_arr as $k => $v) {
                    $tag = "";
                    //查询当前表该字段的格式
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$k] . '1', $v);
                    $objPHPExcel->getActiveSheet()->getColumnDimension($xls_arr[$k])->setWidth(24);
                    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(24);
                    #居中
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
            }
            $tag = 2;
            
            
            if ($list) {
                
                foreach ($list as $k=>$v) {
                    if ($v['fengcun'] == 1) {
                        $list[$k]['suc'] = 3; 
                    } else {
                        if ($v['collate_product'] == 2 || $v['collate_productout'] == 2 || $v['collate_reward'] == 2) {
                            $list[$k]['suc'] = 1; 
                        } elseif ($v['collate_product'] == 1 && $v['collate_reward'] == 1 && $v['collate_productout'] == 1) {
                            $list[$k]['suc'] = 2; 
                        } elseif ($v['collate_product'] == 0 && $v['collate_reward'] == 0 && $v['collate_productout'] == 0) {
                            
                        } else {
                            $list[$k]['suc'] = 1; 
                        }
                    }
                }
                
                foreach ($list as $k=>$v) {
                    $i=0;
                    foreach ($v as $k1=>$v1) {
                        #字段过滤
                        foreach ($fields as $k2=>$v2) {
                            if ($k2 == $k1) {
                                if ($v2['type'] == 'select' || $v2['type'] == 'radio') {
                                    #下拉选项
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $options = $v2['setup']['options'];
                                    $options = explode("\n",$v2['setup']['options']);
                                    foreach($options as $r) {
                                        $v3 = explode("|",$r);
                                        $k3 = trim($v3[1]);
                                        if ($k3 == $v1) {
                                            $v1 = $v3[0];
                                        }
                                    }
                                } elseif ($v2['type'] == 'fkey') {
                                    #外键
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $v1 = db($v2['setup']['modname'])->where("id='{$v1}'")->value($v2['setup']['keyname']);
                                } elseif ($v2['type'] == 'datetime') {
                                    if ($v1) {
                                        $v1 = date('Y-m-d', $v1);
                                    } else {
                                        $v1 = "";
                                    }
                                }
                            }
                        }
                        #$v1 = "\t".$v1."\t";
                        
                        switch($v['suc']) {
                            case 0:
                                break;
                            case 1:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF6666');
                                break;
                            case 2:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCFFCC');
                                break;
                            case 3:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
                                break;
                        }
                        
                        //$objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.$j,$result[1],PHPExcel_Cell_DataType::TYPE_STRING);
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$i] . $tag, $v1);
                        $objPHPExcel->getActiveSheet()->getRowDimension($tag)->setRowHeight(24);
                        $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $i++;
                    }
                    $tag++;
                }
            }
            
            // Rename worksheet
            $objPHPExcel->getActiveSheet()->setTitle("{$mod_info['title']}");
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $objPHPExcel->setActiveSheetIndex(0);
            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $mod_info['title'] . '导出文件.xls"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter->save('php://output');
            exit;
        } else {
            $post = input("post.");
            $modid = $post['id'];
            $sel_map = $this->helper->getMap($post, $modid);
            #存入session
            $rs = array();
            $rs['model_id'] = $modid;
            $rs['sel_map'] = $sel_map;
            Session::set('export_sess', $rs); 
            exit;
        }
    }
    
    public function finacial() {
        #来自跳转
        $from = input("from");
        $sess = session('export_sess');
        
        if ($sess && $from==1) {
            #获取导出列表字段
            $modid = $sess['model_id'];
            $sel_map = $sess['sel_map'];
            $fields = $this->helper->getLfield($modid);#列表字段
            if ($fields) {
                $fields_str = "";
                $f_arr = array();
                foreach ($fields as $k=>$v) {
                    $f_arr[] = $v['name'];
                    $fields_str .= $k.",";
                }
                $fields_str = rtrim($fields_str,',');
            }
            $fields_str .= ",collate,repeat,fengcun";
            
            #模型
            $mod_info = $this->module_mod->where("id='{$modid}'")->find();
            #列表
            $list = db($mod_info['name'])->where($sel_map)->where("istrash=0")->field($fields_str)->select();
            
            
            #删除session
            Session::delete('export_sess');
            \think\Loader::import('PHPExcel.PHPExcel');
            \think\Loader::import('PHPExcel.IOFactory.PHPExcel_IOFactory');
            $objPHPExcel = new \PHPExcel();
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            // Set document properties
            $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                    ->setLastModifiedBy("Maarten Balliauw")
                    ->setTitle("Office 2007 XLSX Test Document")
                    ->setSubject("Office 2007 XLSX Test Document")
                    ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                    ->setKeywords("office 2007 openxml php")
                    ->setCategory("Test result file");

            $xls_arr = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ");
            if ($f_arr) {
                foreach ($f_arr as $k => $v) {
                    $tag = "";
                    //查询当前表该字段的格式
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$k] . '1', $v);
                    $objPHPExcel->getActiveSheet()->getColumnDimension($xls_arr[$k])->setWidth(24);
                    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(24);
                    #居中
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
            }
            $tag = 2;
            
            
            if ($list) {
                
                foreach ($list as $k=>$v) {
                    if ($v['fengcun'] == 1) {
                        $list[$k]['suc'] = 3; 
                    } else {
                        if ($v['repeat'] == 1) {
                            $list[$k]['suc'] = 4; 
                        } else {
                            if ($v['collate'] == 1) {
                                $list[$k]['suc'] = 2; 
                            } elseif ($v['collate'] == 2) {
                                $list[$k]['suc'] = 1; 
                            }
                        }
                    }
                }
                
                foreach ($list as $k=>$v) {
                    $i=0;
                    foreach ($v as $k1=>$v1) {
                        #字段过滤
                        foreach ($fields as $k2=>$v2) {
                            if ($k2 == $k1) {
                                if ($v2['type'] == 'select' || $v2['type'] == 'radio') {
                                    #下拉选项
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $options = $v2['setup']['options'];
                                    $options = explode("\n",$v2['setup']['options']);
                                    foreach($options as $r) {
                                        $v3 = explode("|",$r);
                                        $k3 = trim($v3[1]);
                                        if ($k3 == $v1) {
                                            $v1 = $v3[0];
                                        }
                                    }
                                } elseif ($v2['type'] == 'fkey') {
                                    #外键
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $v1 = db($v2['setup']['modname'])->where("id='{$v1}'")->value($v2['setup']['keyname']);
                                } elseif ($v2['type'] == 'datetime') {
                                    if ($v1) {
                                        $v1 = date('Y-m-d', $v1);
                                    } else {
                                        $v1 = "";
                                    }
                                }
                            }
                        }
                        #$v1 = "\t".$v1."\t";
                        
                        switch($v['suc']) {
                            case 0:
                                break;
                            case 1:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF6666');
                                break;
                            case 2:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCFFCC');
                                break;
                            case 3:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
                                break;
                            case 4:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ACD6FF');
                                break;
                        }
                        
                        //$objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.$j,$result[1],PHPExcel_Cell_DataType::TYPE_STRING);
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$i] . $tag, $v1);
                        $objPHPExcel->getActiveSheet()->getRowDimension($tag)->setRowHeight(24);
                        $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $i++;
                    }
                    $tag++;
                }
            }
            
            // Rename worksheet
            $objPHPExcel->getActiveSheet()->setTitle("{$mod_info['title']}");
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $objPHPExcel->setActiveSheetIndex(0);
            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $mod_info['title'] . '导出文件.xls"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter->save('php://output');
            exit;
        } else {
            $post = input("post.");
            $modid = $post['id'];
            $sel_map = $this->helper->getMap($post, $modid);
            #存入session
            $rs = array();
            $rs['model_id'] = $modid;
            $rs['sel_map'] = $sel_map;
            Session::set('export_sess', $rs); 
            exit;
        }
    }
    
    public function alipay() {
        #来自跳转
        $from = input("from");
        $sess = session('export_sess');
        
        if ($sess && $from==1) {
            #获取导出列表字段
            $modid = $sess['model_id'];
            $sel_map = $sess['sel_map'];
            $fields = $this->helper->getLfield($modid);#列表字段
            if ($fields) {
                $fields_str = "";
                $f_arr = array();
                foreach ($fields as $k=>$v) {
                    $f_arr[] = $v['name'];
                    $fields_str .= $k.",";
                }
                $fields_str = rtrim($fields_str,',');
            }
            $fields_str .= ",collate,repeat,fengcun";
            
            #模型
            $mod_info = $this->module_mod->where("id='{$modid}'")->find();
            #列表
            $list = db($mod_info['name'])->where($sel_map)->where("istrash=0")->field($fields_str)->select();
            
            
            #删除session
            Session::delete('export_sess');
            \think\Loader::import('PHPExcel.PHPExcel');
            \think\Loader::import('PHPExcel.IOFactory.PHPExcel_IOFactory');
            $objPHPExcel = new \PHPExcel();
            $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
            // Set document properties
            $objPHPExcel->getProperties()->setCreator("Maarten Balliauw")
                    ->setLastModifiedBy("Maarten Balliauw")
                    ->setTitle("Office 2007 XLSX Test Document")
                    ->setSubject("Office 2007 XLSX Test Document")
                    ->setDescription("Test document for Office 2007 XLSX, generated using PHP classes.")
                    ->setKeywords("office 2007 openxml php")
                    ->setCategory("Test result file");

            $xls_arr = array("A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z", "AA", "AB", "AC", "AD", "AE", "AF", "AG", "AH", "AI", "AJ", "AK", "AL", "AM", "AN", "AO", "AP", "AQ", "AR", "AS", "AT", "AU", "AV", "AW", "AX", "AY", "AZ");
            if ($f_arr) {
                foreach ($f_arr as $k => $v) {
                    $tag = "";
                    //查询当前表该字段的格式
                    $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$k] . '1', $v);
                    $objPHPExcel->getActiveSheet()->getColumnDimension($xls_arr[$k])->setWidth(24);
                    $objPHPExcel->getActiveSheet()->getRowDimension('1')->setRowHeight(24);
                    #居中
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
                    $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$k] . '1')->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                }
            }
            $tag = 2;
            
            
            if ($list) {
                
                foreach ($list as $k=>$v) {
                    if ($v['fengcun'] == 1) {
                        $list[$k]['suc'] = 3; 
                    } else {
                        if ($v['repeat'] == 1) {
                            $list[$k]['suc'] = 4; 
                        } else {
                            if ($v['collate'] == 1) {
                                $list[$k]['suc'] = 2; 
                            } elseif ($v['collate'] == 2) {
                                $list[$k]['suc'] = 1; 
                            }
                        }
                    }
                }
                
                foreach ($list as $k=>$v) {
                    $i=0;
                    foreach ($v as $k1=>$v1) {
                        #字段过滤
                        foreach ($fields as $k2=>$v2) {
                            if ($k2 == $k1) {
                                if ($v2['type'] == 'select' || $v2['type'] == 'radio') {
                                    #下拉选项
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $options = $v2['setup']['options'];
                                    $options = explode("\n",$v2['setup']['options']);
                                    foreach($options as $r) {
                                        $v3 = explode("|",$r);
                                        $k3 = trim($v3[1]);
                                        if ($k3 == $v1) {
                                            $v1 = $v3[0];
                                        }
                                    }
                                } elseif ($v2['type'] == 'fkey') {
                                    #外键
                                    $v2['setup'] = is_array($v2['setup']) ? $v2['setup'] : string2array($v2['setup']);
                                    $v1 = db($v2['setup']['modname'])->where("id='{$v1}'")->value($v2['setup']['keyname']);
                                } elseif ($v2['type'] == 'datetime') {
                                    if ($v1) {
                                        $v1 = date('Y-m-d', $v1);
                                    } else {
                                        $v1 = "";
                                    }
                                }
                            }
                        }
                        #$v1 = "\t".$v1."\t";
                        
                        switch($v['suc']) {
                            case 0:
                                break;
                            case 1:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FF6666');
                                break;
                            case 2:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCFFCC');
                                break;
                            case 3:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
                                break;
                            case 4:
                                $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('ACD6FF');
                                break;
                        }
                        
                        //$objPHPExcel->getActiveSheet()->setCellValueExplicit('B'.$j,$result[1],PHPExcel_Cell_DataType::TYPE_STRING);
                        $objPHPExcel->setActiveSheetIndex(0)->setCellValue($xls_arr[$i] . $tag, $v1);
                        $objPHPExcel->getActiveSheet()->getRowDimension($tag)->setRowHeight(24);
                        $objPHPExcel->getActiveSheet()->getStyle($xls_arr[$i] . $tag)->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
                        $i++;
                    }
                    $tag++;
                }
            }
            
            // Rename worksheet
            $objPHPExcel->getActiveSheet()->setTitle("{$mod_info['title']}");
            // Set active sheet index to the first sheet, so Excel opens this as the first sheet
            $objPHPExcel->setActiveSheetIndex(0);
            // Redirect output to a client’s web browser (Excel5)
            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $mod_info['title'] . '导出文件.xls"');
            header('Cache-Control: max-age=0');
            // If you're serving to IE 9, then the following may be needed
            header('Cache-Control: max-age=1');

            // If you're serving to IE over SSL, then the following may be needed
            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter->save('php://output');
            exit;
        } else {
            $post = input("post.");
            $modid = $post['id'];
            $sel_map = $this->helper->getMap($post, $modid);
            #存入session
            $rs = array();
            $rs['model_id'] = $modid;
            $rs['sel_map'] = $sel_map;
            Session::set('export_sess', $rs); 
            exit;
        }
    }
    
}