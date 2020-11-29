<?php
/**
 * 爬取西祠代理数据
 */


//修改数据
foreach($argv as $v)
{
    list($k,$v) = explode('=',$v);
    $k=$v;
}

//地址
$url = isset($url)?$url:'https://www.xicidaili.com/nn/{n}';
//开始页数
$start = isset($start)?$start:1;
//结束页数 0不限制
$end = isset($end)?$end:0;

do{
    $url = str_replace('{n}',$start,$url);
    $str = file_get_contents($url);
    //正则匹配
    $rule = '/<tr(.*)>(.*)<\/tr>/U';
    $rule = "/<tr(.*?)>(.*?)<\/tr>/ism";
    if(preg_match_all($rule,$str,$data)) {
        $string = '第'.$start.'页';
        $rule = "/<td(.*?)>(.*?)<\/td>/ism";
        foreach($data[0] as $v){
            if(!preg_match_all($rule,$v,$content) && !strstr($v,'alt="Cn"'))
                continue;
            $a = '';
            for($i=1;$i<4;$i++){
                $string .= $a.str_replace(['<td>','</td>',"\n",' '],['','','',''],$content[0][$i]);
                $a = ',';
            }
            $string .= "\n";
        }
        file_put_contents('ip_port_address.php',$string,FILE_APPEND);
    }else {
        break;
    }
    $start++;
    sleep(10);
}while(file_get_contents('config.php') && (0 == $end || $end >= $start-1));

die($start.'-'.$end.'数爬取完毕');
