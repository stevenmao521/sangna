<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // 数据库类�?
    'type'            => 'mysql',
    // 服务器地�?
    //'hostname'        => '47.92.173.63',
    'hostname'        => '127.0.0.1',
    // 数据库名
    'database'        => 'clt_bee',
    // 用户�?
    'username'        => 'root',
    // 密码
    //'password'        => 'mao250278506',
    'password'        => 'maojianyu',
    // 端口
    'hostport'        => '3306',
    // 连接dsn
    'dsn'             => '',
    // 数据库连接参�?
    'params'          => [],
    // 数据库编码默认采用utf8
    'charset'         => 'utf8',
    // 数据库表前缀
    'prefix'          => 'clt_',
    // 数据库调试模�?
    'debug'           => true,
    // 数据库部署方�?:0 集中�?(单一服务�?),1 分布�?(主从服务�?)
    'deploy'          => 0,
    // 数据库读写是否分�? 主从式有�?
    'rw_separate'     => false,
    // 读写分离�? 主服务器数量
    'master_num'      => 1,
    // 指定从服务器序号
    'slave_no'        => '',
    // 是否严格�?查字段是否存�?
    'fields_strict'   => false,
    // 数据集返回类�?
    'resultset_type'  => 'array',
    // 自动写入时间戳字�?
    'auto_timestamp'  => false,
    // 时间字段取出后的默认时间格式
    'datetime_format' => 'Y-m-d H:i:s',
    // 是否�?要进行SQL性能分析
    'sql_explain'     => false,
];
