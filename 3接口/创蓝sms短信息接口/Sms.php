<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * 短信类
 */
class Sms {

    private $gate_way;
    private $username;
    private $password;
    private $extno;

    public function __construct() {
        $config = get_config_item('sms');
        if (!$config) {
            throw new \Exception("您必须配置短信接口");
        }
        $this->gate_way = $config['gate_way'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->extno = $config['extno'];
    }

    /**
     * 短信发送 用例
     */
    public function sendSMS($mobile, $content, $sign = "") {
        if(!$sign){
            $sign = $this->extno;
        }
        if (is_array($mobile)) {
            $mobile = implode(',', $mobile);
        }
        //$encode = mb_detect_encoding($content, array("UTF-8", "GB2312", "GBK"));
        //if ($encode == "UTF-8") {
        //    $content = iconv('UTF-8', 'gbk//TRANSLIT', $sign.$content);
        //}
        $content = $sign.$content;
        log_message("error",'content===='.$content);
        $data = array(
            'account'  => $this->username,
            'password' => $this->password,
            'msg' => urlencode($content),
            'phone' => $mobile,
            'report' => false
        );

//        $data = array_filter($data);
        //$data = http_build_query($data);
        //$result = file_get_contents($this->gate_way . "?" . $data);
        $data = json_encode($data);
        $result = http_post_like($this->gate_way, $data);
        if(!is_null(json_decode($result))){
                $output=json_decode($result,true);
                if(isset($output['code'])  && $output['code']=='0'){
                        return 1;
                }
        }
        log_message('error', $result);
        return false;
    }

}

