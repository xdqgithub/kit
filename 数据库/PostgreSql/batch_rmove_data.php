<?php

ignore_user_abort(); // run script in background
set_time_limit(0); // run script forever

//ip
$addr = $argv[1];

//端口
$port = $argv[2];

//数据库名
$dbname = $argv[3];

//链接数据库链接字符串
$conn_str = "host=$addr port=$port dbname=$dbname user=postgres password=zhongyi";

//链接句柄
$pg = pg_connect($conn_str);

//链接失败
!$pg && die('链接失败');

//获取数据表
$tables  = $argv[4];

//单次转移数据量
$length = isset($argv[5])?$argv[5]:100;

//储存表
$toTable = $argv[6];

//根据‘，’分割表名
$tables = explode(',',$tables);

//循环表进行添加数据
foreach($tables as $table) {

    //开始值
    $start  = is_file($table)?file_get_contents($table)?:0:0;

    //获取表长度
    $sql = "select count(1) from $table";

    $count = pg_fetch_row(pg_query($pg,$sql))[0];
    echo '表'.$table.'数据条数'.$count."\n";
    //循环转移数据
    do {
        //生成sql
        $sql = "INSERT INTO $toTable select * from $table LIMIT $length OFFSET $start";

        //提交SQL
        $rt = pg_query($pg, $sql);

        echo date('Y-m-d H:i:s').'已复制条数'.$start."\n";

        //设置开始时间
        $start += $length;

        //记录已转移数据至表名文件
        if ($rt) file_put_contents($table, $start);

    } while ($start < ($count+$length));
    echo '表'.$table.'数据至'.$toTable.'传输完毕'."\n";
}

//转移完毕
die('全部数据已转移完毕！');

//nohup php index1.php 127.0.0.1 11710 collector hcep_device_info_101_copy,hcep_device_info_20190902_befor 10000 hcep_device_info_101 &
//[1] 10286

