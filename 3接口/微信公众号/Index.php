<?php
namespace app\index\controller;

use ass\index\model\User;
use think\Controller;
use think\Db;
use think\Request;


class Index extends Controller
{
    protected $postObj;
    protected $goObj;
    public function index()
    {
        return '<style type="text/css">*{ padding: 0; margin: 0; } .think_default_text{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:)</h1><p> ThinkPHP V5<br/><span style="font-size:30px">十年磨一剑 - 为API开发设计的高性能框架</span></p><span style="font-size:22px;">[ V5.0 版本由 <a href="http://www.qiniu.com" target="qiniu">七牛云</a> 独家赞助发布 ]</span></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=9347272" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="ad_bd568ce7058a1091"></think>';
    }

    public function link(){
        $request = Request::instance();

        $signature = $request->param('signature');
        $timestamp = $request->param('timestamp');
        $nonce = $request->param('nonce');
        $echostr = $request->param('echostr');
        $token = "abc";

        $arr = [$token,$nonce,$timestamp];
        sort($arr,SORT_STRING);
        $str = sha1(implode('',$arr));
        if($str == $signature) {
            if($echostr){
                echo $echostr;
            }else{
                $this->backMsg1();
            }
        }
    }

    public function backMsg(){
        header("content-type:text/html;charset=utf-8");
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
        logs($postStr);
        $postObj = simplexml_load_string($postStr);
        $type=$postObj->MsgType;
        if($type == ('text'||"voice")) {
            $word = $postObj->Content?$postObj->Content:mb_substr($postObj->Recognition,0,mb_strlen($postObj->Recognition,'utf8')-1,'utf8');
            $result = Db::table('words')->where('word',$word)->find();
            $content = $result['back_word'];
            $message = "没听懂";
            if(!$result){
                $result = Db::table('music')->where('music_name',$word)->find();
                $content = "歌曲：".$word."\n作者：".$result['singer']."\n发行时间：".$result['time'];
            }
            if(!$result) echo backMsgText($message,$postObj->ToUserName,$postObj->FromUserName);
            echo backMsgText($content,$postObj->ToUserName,$postObj->FromUserName);

        }
    }

    public function backMsg1(){
        header("content-type:text/html;charset=utf-8");
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
        logs($postStr);
        $this->postObj =simplexml_load_string($postStr);
        $postObj = simplexml_load_string($postStr);
        $type=$postObj->MsgType;
        if($type =='text'|| $type =="voice") {
            $word = $postObj->Content?$postObj->Content:mb_substr($postObj->Recognition,0,mb_strlen($postObj->Recognition,'utf8')-1,'utf8');

            $result = Db::table('user')->where('user_name',$this->postObj->FromUserName)->find();

            if($word == 'yzdd' ){
                if(!$result){
                    //提示输入s 开始答题 q 退出答题
                    Db::table('user')->insert(['user_name'=>$this->postObj->FromUserName,'y_status'=>2],true);
                    echo backMsgText('输入s开始答题/q退出答题',$postObj->ToUserName,$postObj->FromUserName);
                    exit;
                }
            }else if($word == 's'  ){
                //检查用户是否时以游戏身份进来
                $rt = Db::table('user')->where('user_name',$this->postObj->FromUserName)->where('y_status',2)->find();

                //身份验证正确 开始答题 出题
                if($rt){
                    //获取题目
                    $result = Db::table('go')->select();

                    if(!$result){
                        echo backMsgText('获取题目失败',$postObj->ToUserName,$postObj->FromUserName);
                        Db::table('user')->where('user_name',$this->postObj->FromUserName)->update(['y_status'=>0]);
                        exit;
                    }

//                    //随机取题
                    $num = rand(0,count($result)-1);
                    $result = $result[$num];

                    $g_id = $rt['g_id'].",".$result['id'];
                      //修改用户答题数据
                    $data = [
                        'user_name'=>$this->postObj->FromUserName,
                        'y_status'=>1,
                        'g_id'=>$g_id,
                        'answer'=>$result['answer'],
                    ];

//                    $user = new User();
//                    $result2 = $user->uptUser($data);
                    $result2 = Db::table('user')->where('user_name',$this->postObj->FromUserName)->update($data);

                    if(!$result2){
                        echo backMsgText('创建用户答题失败',$postObj->ToUserName,$postObj->FromUserName);
                        Db::table('user')->where('user_name',$this->postObj->FromUserName)->update(['y_status'=>0,'g_id'=>'']);
                        exit;
                    }

                    echo backMsgText("题目：".$result['question'],$postObj->ToUserName,$postObj->FromUserName);
                    exit;
                }
            }else if($word == 'q'){
                //检查用户是否时以游戏身份进来
                $rt = Db::table('user')->where('user_name',$this->postObj->FromUserName)->where('y_status',1)->find();
                if($rt){
                    Db::table('user')->where('user_name',$this->postObj->FromUserName)->update(['y_status'=>0,'g_id'=>'',"answer"=>'']);
                    //退出答题
                    echo backMsgText('退出答题',$postObj->ToUserName,$postObj->FromUserName);
                    exit;
                }
            }

            if($result && $result['y_status']==1){
                //判断
                if($word == $result['answer']){
                    //答对继续答题 记录用户成绩
                    $grade = $result['grade']+1;
                    Db::table('user')->where('user_name',$this->postObj->FromUserName)->update(['grade'=>$grade]);

                    //获取题目
                    $rt = Db::table('go')->whereNotIn('id',$result['g_id'])->select();

                    //未获取到数据提示
                    if(!$rt){
                        echo backMsgText('获取题目失败',$postObj->ToUserName,$postObj->FromUserName);
                        exit;
                    }

//                    //随机取题
                    $num = rand(0,count($rt));
                    $rt = $rt[$num];
                    $g_id = $result['g_id'].",".$rt['id'];
                    //修改用户答题数据
                    $data = [
                        'g_id'=>$g_id,
                        'answer'=>$rt['answer'],
                        'grade'=>$result['grade']+1,
                    ];

                    //修改用户答题数据
                    $result2 = Db::table('user')->where('user_name',$this->postObj->FromUserName)->update($data);

                    if(!$result2){
                        echo backMsgText('创建用户答题失败',$postObj->ToUserName,$postObj->FromUserName);
                        Db::table('user')->where('user_name',$this->postObj->FromUserName)->update(['y_status'=>0,'g_id'=>'']);
                        exit;
                    }

                    echo backMsgText('题目:'.$rt['question'],$postObj->ToUserName,$postObj->FromUserName);
                    exit;

                }else{
                    //答错返回
                    echo backMsgText('回答错误，退出答题',$postObj->ToUserName,$postObj->FromUserName);
                    Db::table('user')->where('user_name',$this->postObj->FromUserName)->update(['y_status'=>0,'g_id'=>'','answer'=>'']);
                    exit;
                }

            }else if($result && $result['y_status']==2){
                echo backMsgText('输入s开始答题/q退出答题',$postObj->ToUserName,$postObj->FromUserName);
            }else if($result && $result['y_status'] == 0 && $word == 'yzdd'){
                //参加过游戏的玩家返回得分以及排行


                $content = "你的分数为：".$result['grade'];
                echo backMsgText($content,$postObj->ToUserName,$postObj->FromUserName);

            }else if(mb_strpos("a".$word,'音乐')){
                $word = implode('',explode('音乐',$word));

                $result = Db::table('music')->where('music_name',$word)->find();

                if(!$result){
                    echo backMsgText('没有此歌曲信息',$postObj->ToUserName,$postObj->FromUserName);
                    exit;
                }

                echo backMsgMusic($word,$result['url'],$result['burl'],$result['description'],$postObj->ToUserName,$postObj->FromUserName);

            }elseif(mb_strpos('a'.$word,'游戏')){
                $word = implode('',explode('游戏',$word));
                $result = Db::table('game')->where('game_name',$word)->find();
                if(!$result){
                    echo backMsgText('没有此游戏',$postObj->ToUserName,$postObj->FromUserName);
                    exit;
                }

                echo backMsgPic($word,$result['url'],$result['description'],$result['picture'],$postObj->ToUserName,$postObj->FromUserName);

            }else if(mb_strstr($word,'天气')){

                $url = "http://www.xunsearch.com/scws/api.php";
                $data['data'] = $word;
                $data['respond'] = 'json';

                $result = http_request($url,$data);
                $json = json_decode($result,true);
                $arr = [];
                foreach($json['words'] as $v){
                    if($v['attr'] == 'ns'){
                        $arr[]=$v['word'];
                    }
                }
                if([] == $arr){
                    echo  backMsgText('未知城市',$postObj->ToUserName,$postObj->FromUserName);
                }
                $content = '';

                foreach($arr as $v){
                    $key = "V35gWgKSicqNkYgngHcADggvl52A4fTB";
                    $url = "http://api.map.baidu.com/telematics/v3/weather?location={$v}&output=json&ak=".$key;
                    $result = http_request($url);
                    $json = json_decode($result,true);

                    if(0 == $json['error'] ){
                        $content .= "城市：".$v."\n日期：".$json['date']."\npm25:".$json['results'][0]['pm25']."\n气温:".$json['results'][0]['weather_data'][0]['temperature']."\n";
                    }

                }
                if('' == $content) echo backMsgText('请输入国内城市',$postObj->ToUserName,$postObj->FromUserName);

                    echo backMsgText($content,$postObj->ToUserName,$postObj->FromUserName);
            }else{
                //自定义回复
//
//                $result = Db::table('words')->where('word',$word)->find();
//                if(!$result){
//                    echo backMsgText('没听懂',$postObj->ToUserName,$postObj->FromUserName);
//                    exit;
//                }
//                $content = $result['back_word'];
//
//                echo  backMsgText($content,$postObj->ToUserName,$postObj->FromUserName);

                //图灵机器人接口实现回复

                $url = "http://openapi.tuling123.com/openapi/api/v2";
                $data = '{
                        "reqType":0,
                        "perception": {
                            "inputText": {
                                "text": "'.$word.'"
                            },
                            "inputImage": {
                                "url": "imageUrl"
                            },
                        },
                        "userInfo": {
                            "apiKey": "c264f4797da041398f8a99b834b8806f",
                            "userId": "asdgfasdgasdgasdf"
                        }
                    }';
                $result = http_request($url,$data);
                $json = json_decode($result,true);
                $arr = ['url'=>'','text'=>'','name'=>''];
                foreach($json['results'] as $v){
                    //回复链接时使用
                    if(isset($v['values']['url'])) $arr['url'] = $v['values']['url'];
                    if(isset($v['values']['text']))$arr['text'] = $v['values']['text'];
                    //回复新闻信息时使用
                    if(isset($v['values']['news'])){
                        $b = $v['values']['news'][0];
                        echo backMsgPic($b['name'],$b['detailurl'],$b['info'],$b['icon'],$postObj->ToUserName,$postObj->FromUserName);
                        exit;
                    }
                }

                if(!$arr['text']) echo  backMsgText('没听懂',$postObj->ToUserName,$postObj->FromUserName);
                $content = $arr['text'];

                if($arr['url'])  $content = "<a href='".$arr['url']." '>".$content."</a>";


                echo backMsgText($content,$postObj->ToUserName,$postObj->FromUserName);
            }
        }else if($type == 'image'){

            $url='https://api-cn.faceplusplus.com/facepp/v3/detect';
            $data['api_key'] = '5xAt9NFAzXwqCw5zGZu769fXO2k5uU14';
            $data['api_secret'] = 'JbWHijiJXD6dWOFIZX3PHLQeM28zfMSF';
            $data['image_url'] = $postObj->PicUrl;
            $data['return_attributes'] = 'age,beauty,gender';
            $result = http_request($url,$data);
            $json = json_decode($result,true);
            $attr = $json['faces'][0]['attributes'];
            $content = "测试结果\n年龄：".$attr['age']['value']."\n性别：";

            if($attr['gender']['value']=='Female'){
                $content.= '女';
            }else{
                $content.= "男";
            }

            if($attr['age']['value'] == 'Female'){
                $content .= "\n颜值：". $attr['beauty']['female_score'];
            }else{
                $content .="\n颜值：". $attr['beauty']['male_score'];
            }

            echo backMsgText($content,$postObj->ToUserName,$postObj->FromUserName);
        }else if($type == 'event'){

            echo $this->eventMsg();
        }
    }

    public function eventMsg(){
        $event = $this->postObj->Event;
        if($event == 'subscribe'){
            return  backMsgText("欢迎关注",$this->postObj->ToUserName,$this->postObj->FromUserName);
        }elseif($event == 'unsubscribe'){
            echo 1;
        }else if ($event == "SCAN"){
            //二维码扫描进入判断
            if($this->postObj->EventKey == '123'){
                $content = "姓名：谢东权，\n 出生年月：1991.11.26，\n性别：男，\n心上人：你猜";
                return   backMsgText($content,$this->postObj->ToUserName,$this->postObj->FromUserName);
            }else if($this->postObj->EventKey == '321'){
                $content = "今天芹菜价格有点高";
                return  backMsgText($content,$this->postObj->ToUserName,$this->postObj->FromUserName);
            }
        }elseif(strtolower($event)=='click'){
            //菜单点击进入
            if($this->postObj->EventKey == "music"){
                //音乐
                $url = "http://wx.devoe100.com/%E8%99%8E%E4%BA%8C%20-%20%E4%B8%80%E7%99%BE%E4%B8%87%E4%B8%AA%E5%8F%AF%E8%83%BD.flac";
                return  backMsgMusic('音乐',$url,$url,'一万种可能',$this->postObj->ToUserName,$this->postObj->FromUserName);
            }else if($this->postObj->EventKey == 'image'){
                //图片
                $media_id = "Op20VINf9YaxN44Txc1aNOnD74oX5KT27wQLHy5WGGm00GiyQX-xKcLNCZgLiylf";
                return $this->backPicture($media_id);
            }else if($this->postObj->EventKey == 'voice'){
                //语音
                $media_id = "AOM-tt3AXMf_kSMUE9vwiCJZ2TjOKxB4mEaAla3z8WO9fUXKK801_iNFYnu1ocqw";
                return $this->backMusic($media_id);
            }else if($this->postObj->EventKey == 'pic'){
                //图文
                return backMsgPic('游戏','http://wx.devoe100.com/index.php/index/Index/user','摇一摇','http://wx.devoe100.com/favicon.ico',$this->postObj->ToUserName,$this->postObj->FromUserName);
            }else if($this->postObj->EventKey == 'video'){
                //视频
                $media_id = "TtZrVUYomz6wm9s8nzFagV_mGJ8sQDPRt9YH0qbI6hXz6ui5XtNyDv6pKf94Hs2u";
                return $this->backVideo($media_id,'视频','介绍');
            }else if($this->postObj->EventKey == 'text'){
                return  backMsgText("么么哒",$this->postObj->ToUserName,$this->postObj->FromUserName);
            }
        }
    }

    //生成带有参数的二位码
    public function d2(){
        $token = '19_Hxs838HLZK8UHl_cOkDnuAq8jIVVXBoiZyP-6GVlxkCSjs7uauUE0lXtu2jwRRtJLMF-g62KBJrDPKrXPYkoUtDvF_iznVAVypFrumd7YkyWUtobEG3Y0VfPcZt4OHZwSr0vfdbxCVx2fr0nCJKiAGAGNO';
        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$token;
        $data = '{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 321}}}';

        $reuslt = http_request($url,$data);
        $json = json_decode($reuslt,true);

        echo "<pre>";
        print_r($json);
        echo "</pre>";



    }

    public function aaa(){
        header("content-type:text/html;charset=utf-8");
//        $city = "哈尔滨";
//
//        $key = "V35gWgKSicqNkYgngHcADggvl52A4fTB";
////                $city = '哈尔滨';
//        $url = "http://api.map.baidu.com/telematics/v3/weather?location={$city}&output=json&ak=".$key;
//        $result = http_request($url);
//        $json = json_decode($result,true);

        //图灵机器人
//        $url = "http://openapi.tuling123.com/openapi/api/v2";
//        $data = '{
//                        "reqType":0,
//                        "perception": {
//                            "inputText": {
//                                "text": "鱼香肉丝"
//                            },
//                            "inputImage": {
//                                "url": "imageUrl"
//                            },
//                        },
//                        "userInfo": {
//                            "apiKey": "cf92663679c945deafe5340260a50125",
//                            "userId": "wasgfa"
//                        }
//                    }';
//        $result = http_request($url,$data);
//        $json = json_decode($result,true);     Vw5F7ic19-pUUvifTDCOxsAEHnoPkUGc
        //人脸识别
//        $url='https://api-cn.faceplusplus.com/facepp/v3/detect';
//        $data['api_key'] = '5xAt9NFAzXwqCw5zGZu769fXO2k5uU14';
//        $data['api_secret'] = 'JbWHijiJXD6dWOFIZX3PHLQeM28zfMSF';
//        $data['image_url'] = "https://ss1.bdstatic.com/70cFvXSh_Q1YnxGkpoWK1HF6hhy/it/u=2085021334,2351168293&fm=27&gp=0.jpg";
//        $data['return_attributes'] = 'age,beauty,gender';
//        $result = http_request($url,$data);
//        $json = json_decode($result,true);

            //生成token
        $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxb1496d1ff1ccda97&secret=70a883d5ebf458c76b51504af0fe6dc8';

        //返回值 '{"access_token":"19_72M7nr2aE05g_icZLt90kfGhs5DXn20FBSAbGYXJZRSsTEQXX5YgpZAXT5f-RkBt6B8rU-x9SAB4kdPF7fwKMlsEZipoURrD3DTdMpEpgwxMLHaO1l8z3gw66g5HFOGjDQbygybSr4zrRue2WTShAHANKJ","expires_in":7200}';
        //获取ticket
//        $token = '19_72M7nr2aE05g_icZLt90kfGhs5DXn20FBSAbGYXJZRSsTEQXX5YgpZAXT5f-RkBt6B8rU-x9SAB4kdPF7fwKMlsEZipoURrD3DTdMpEpgwxMLHaO1l8z3gw66g5HFOGjDQbygybSr4zrRue2WTShAHANKJ';
//        $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.$token;
//        $data = '{"expire_seconds": 604800, "action_name": "QR_SCENE", "action_info": {"scene": {"scene_id": 123}}}';
//
//        $reuslt = http_request($url,$data);
//        $json = json_decode($reuslt,true);
       //返回值
//        Array
//        (
//            [ticket] => gQE48DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyTldhd3NWTGJjb0QxRlhKZ3hzMTgAAgT7codcAwSAOgkA
//            [expire_seconds] => 604800
//                [url] => http://weixin.qq.com/q/02NWawsVLbcoD1FXJgxs18
//    )

        //get方式获取二维码图片
        //'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket=gQHf8DwAAAAAAAAAAS5odHRwOi8vd2VpeGluLnFxLmNvbS9xLzAyVloxc3NvTGJjb0QxRjRTZzFzMTAAAgTFe4dcAwSAOgkA';

        //新增临时素材

        //  https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxb1496d1ff1ccda97&secret=70a883d5ebf458c76b51504af0fe6dc8
        $ak = '19_aZZMSDL2B_QFXlLoToJ0St__AqZAaFYp44lMsB_YXx_LqthSgwjRrbDuLNDbAYPhmcjRlimBQPqpb63T677dq2jEUMAp8OLX_4LTkHpV-6b8AOgifvSm4apXT2kPRNfABACEA';
//        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$ak}&type=image";
//
//        $data['media'] = "@C:/1.jpg";
//
//        $rt = http_request($url,$data);
//
//        var_dump($rt);
//        $json = json_decode($rt,true);
//        echo "<pre>";
//        print_r($json);
//        echo "</pre>";
        //写入数据流形成文件

//         $media_id =  'KbjdOL4wgCYPNjS0iXy9RuVNhv6fcKwl0xoiL-95SMVQxmNAfj_qUSC6_aQcKLO3';
//
//
//        $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token={$ak}&media_id={$media_id}";
//
//        $rt = http_request($url);
//
//        $file = fopen('C:/2.jpg',"w+");
//        fwrite($file,$rt);
//        fclose($file);

        //获取用户信息
//        $user = "o70sY50bHSoJYKc23h4Db2qcGqLY";
//        $url = "https://api.weixin.qq.com/cgi-bin/user/info?access_token={$ak}&openid={$user}&lang=zh_CN";
//
//        $rt = http_request($url);
//
//        $json = json_decode($rt);

        //fixme 自定义菜单
//        $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$ak}";
//        $data = ' {
//                 "button":[
//                 {
//                      "type":"click",
//                      "name":"haha歌曲",
//                      "key":"V1001_TODAY_MUSIC"
//                  },
//                  {
//                       "name":"菜单",
//                       "sub_button":[
//                       {
//                           "type":"view",
//                           "name":"搜索",
//                           "url":"http://www.jd.com/"
//                        },
//                        {
//                           "type":"click",
//                           "name":"啦啦啦下我们",
//                           "key":"V1001_GOOD"
//                        }]
//                   }
//                   ]
//             }';
//
//        $json = http_request($url,$data);
//
//        $json = json_decode($json);

        //fixme 网页授权
//        $appid = "wxb1496d1ff1ccda97";
//        $uri = urlencode('http://wx.devoe100.com/index.php/index/Index/nnn');
//
//        echo $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid={$appid}&redirect_uri={$uri}&response_type=code&scope=snsapi_userinfo&state=123#wechat_redirect";




        $a= 'jjj';
        $b= &$a;
        unset($b);
        $b= 'ddd';
        echo $a;
        

    }

    //微信上传素材
    public function uptMaterial(){
        //图片
//        $ak = '19_OcYhBD0awpxA6psJTXVESNhPfAcGcEDDVxDhk5-Za8OsnY15yXRSaoqg8FISx3ubItHwILYs5PGBmQePntJkWFsJ5kpQfmrpoj8dt-IJThFF0kosHAY6EAq-kTq787s_DTvRrODq48gY5QlgNYAbAGATEG';
//        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$ak}&type=image";
//
//        $data['media'] = "@C:/1.jpg";
//
//        $rt = http_request($url,$data);

//        Array
//        (
//        [type] => image
//        [media_id] => Op20VINf9YaxN44Txc1aNOnD74oX5KT27wQLHy5WGGm00GiyQX-xKcLNCZgLiylf
//        [created_at] => 1552547046
//)
        //音乐 使用本地文件路径


        //视频文件
//        $ak = '19_OcYhBD0awpxA6psJTXVESNhPfAcGcEDDVxDhk5-Za8OsnY15yXRSaoqg8FISx3ubItHwILYs5PGBmQePntJkWFsJ5kpQfmrpoj8dt-IJThFF0kosHAY6EAq-kTq787s_DTvRrODq48gY5QlgNYAbAGATEG';
//        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$ak}&type=video";
//        $data['media'] = "@C:/2.mp4";
//        $rt = http_request($url,$data);

//        Array
//        (
//        [type] => video
//        [media_id] => TtZrVUYomz6wm9s8nzFagV_mGJ8sQDPRt9YH0qbI6hXz6ui5XtNyDv6pKf94Hs2u
//        [created_at] => 1552547111
//)
        //语音文件
//        $ak = '19_OcYhBD0awpxA6psJTXVESNhPfAcGcEDDVxDhk5-Za8OsnY15yXRSaoqg8FISx3ubItHwILYs5PGBmQePntJkWFsJ5kpQfmrpoj8dt-IJThFF0kosHAY6EAq-kTq787s_DTvRrODq48gY5QlgNYAbAGATEG';
//        $url = "https://api.weixin.qq.com/cgi-bin/media/upload?access_token={$ak}&type=voice";
//        $data['media'] = "@C:/2.amr";
//        $rt = http_request($url,$data);

        //语音数据 id  AOM-tt3AXMf_kSMUE9vwiCJZ2TjOKxB4mEaAla3z8WO9fUXKK801_iNFYnu1ocqw

        //图文弄一下就像了去上面


        //模板消息回复设置
        $ak = "19_qW0TWEhqaeYfVM7tQmUQbrqXskg8nl3jjgXE5LgGz0xOwBgXKfUxdhqMNKIyQcwFvTeyxTNnQi9tpIWNg3YNxWW-v_4_qpXUZain8-3PJzrK2PF0BZKUksAtBXPqR26sP2GNb7InBG-0VL0DTCGjABAOXB";
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token={$ak}";
        $data = ' {
           "touser":"o70sY50bHSoJYKc23h4Db2qcGqLY",
           "template_id":"z8DXe2_xFiMbnpYQXwloAhUoh6Pjm0VMLZ2KJRVhAuQ",
           "url":"http://taobao.com",       
           "data":{
                   "tel":{
                       "value":"15645100208",
                       "color":"#ffffff"
                   },
                   "email":{
                       "value":"516640978@qq.com",
                       "color":"#000000"
                   },
                   "name": {
                       "value":"赵一宣",
                       "color":"red"
                   }
           }
       }';

        $rt = http_request($url,$data);

        var_dump($rt);
        $json = json_decode($rt,true);

        echo "<pre>";
        print_r($json);
        echo "</pre>";
        array_fill();
    }

    //回复语音消息
    public function backMusic($media_id){
        $xml = "<xml>
              <ToUserName><![CDATA[{$this->postObj->FromUserName}]]></ToUserName>
              <FromUserName><![CDATA[{$this->postObj->ToUserName}]]></FromUserName>
              <CreateTime>{time()}</CreateTime>
              <MsgType><![CDATA[voice]]></MsgType>
              <Voice>
                <MediaId><![CDATA[{$media_id}]]></MediaId>
              </Voice>
            </xml>";
        return $xml;
    }

    //回复视频消息
    public function backVideo($media_id,$title,$description){
        $xml = "<xml>
               <ToUserName><![CDATA[{$this->postObj->FromUserName}]]></ToUserName>
              <FromUserName><![CDATA[{$this->postObj->ToUserName}]]></FromUserName>
              <CreateTime>{time()}</CreateTime>
              <MsgType><![CDATA[video]]></MsgType>
              <Video>
                <MediaId><![CDATA[{$media_id}]]></MediaId>
                <Title><![CDATA[{$title}]]></Title>
                <Description><![CDATA[{$description}]]></Description>
              </Video>
            </xml>";
        return $xml;
    }

    //回复图片消息
    public function backPicture($media_id){
        $xml = "<xml>
          <ToUserName><![CDATA[{$this->postObj->FromUserName}]]></ToUserName>
              <FromUserName><![CDATA[{$this->postObj->ToUserName}]]></FromUserName>
              <CreateTime>{time()}</CreateTime>
          <MsgType><![CDATA[image]]></MsgType>
          <Image>
            <MediaId><![CDATA[{$media_id}]]></MediaId>
          </Image>
        </xml>";
        return $xml;
    }

    //微信授权网页
    public function nnn(){
        $request = Request::instance();
        $code = $request->param('code');
        $appid = "wxb1496d1ff1ccda97";
        $secret = "70a883d5ebf458c76b51504af0fe6dc8";
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid={$appid}&secret={$secret}&code={$code}&grant_type=authorization_code";

        $result = http_request($url);

        $json = json_decode($result,true);

        echo "<pre>";
        print_r($json);
        echo "</pre>";

        $ak = $json['access_token'];
        $openid = $json['openid'];



        $url = "https://api.weixin.qq.com/sns/userinfo?access_token={$ak}&openid={$openid}&lang=zh_CN";
        $result = http_request($url);
        $json = json_decode($result,true);
        echo "<pre>";
        print_r($json);
        echo "</pre>";

    }

    //自动设置公众号菜单
    public function home(){
        header("content-type:text/html;charset=utf-8");
        $request = Request::instance();
        if($request->isPost()){
            $post = $request->post();
            $a = '';
            $str = '';
            //处理数据 的到json数据
            foreach($post['button'] as  $k=>$v){
                if('' != $v['type'] && '' != $v['name'] && ''!= $v['key']){
                    'view' == $v['type']?$key = 'url':$key='key';
                    $str.= $a.'{    
                      "type":"'.$v['type'].'",
                      "name":"'.$v['name'].'",
                      "'.$key.'":"'.$v['key'].'"
                       }';
                    $a = ",";
                }else if(''==$v['type'] &&  ''!= $v['name'] ){
                        $b= '';
                        $bb = '';
                       foreach($v['sub_button'] as $vv){
                           if('' != $vv['type'] && $vv['name'] && $vv['key']){
                               'view' == $vv['type']?$key = 'url':$key='key';
                               $bb.= $b.'{
                                       "type":"'.$vv['type'].'",
                                       "name":"'.$vv['name'].'",
                                       "'.$key.'":"'.$vv['key'].'"
                                    }';
                               $b=',';
                           }
                       }
                     if('' != $bb){
                         $str.= $a.'{
                       "name":"'.$v['name'].'",
                       "sub_button":['.$bb. "]}";
                         $a = ",";
                     }
                }

            }

            $json = '{"button":['.$str.']}';

            echo $json;

            //查询ak数据
            $old_ak = Db::table('ak')->find();
            echo "<br>";
            echo  $ak = $old_ak['ak'];
             

             //判断ak是否失效，是从新获取；
            if($old_ak['time']+7200 < time()){
                $url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=wxb1496d1ff1ccda97&secret=70a883d5ebf458c76b51504af0fe6dc8';
                $result = http_request($url);
                $ak = json_decode($result,true)['access_token'];
                Db::table('ak')->where('id',1)->update(['ak'=>$ak,'time'=>time()]);
            }

            $url = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token={$ak}";

            $json = http_request($url,$json);


            $json = json_decode($json,true);

            echo "<pre>";
            print_r($json);
            echo "</pre>";
            exit;
        }


        return $this->fetch();

    }


    //方法备份
    public function backMsg2(){
        header("content-type:text/html;charset=utf-8");
        $postStr = $GLOBALS['HTTP_RAW_POST_DATA'];
        logs($postStr);
        $this->postObj =simplexml_load_string($postStr);
        $postObj = simplexml_load_string($postStr);
        $type=$postObj->MsgType;
        if($type == ('text'||"voice")) {
            $word = $postObj->Content?$postObj->Content:mb_substr($postObj->Recognition,0,mb_strlen($postObj->Recognition,'utf8')-1,'utf8');
            if(mb_strpos("a".$word,'音乐')){
                $word = implode('',explode('音乐',$word));

                $result = Db::table('music')->where('music_name',$word)->find();

                if(!$result){
                    echo backMsgText('没有此歌曲信息',$postObj->ToUserName,$postObj->FromUserName);
                    exit;
                }

                echo backMsgMusic($word,$result['url'],$result['burl'],$result['description'],$postObj->ToUserName,$postObj->FromUserName);

            }elseif(mb_strpos('a'.$word,'游戏')){
                $word = implode('',explode('游戏',$word));
                $result = Db::table('game')->where('game_name',$word)->find();
                if(!$result){
                    echo backMsgText('没有此游戏',$postObj->ToUserName,$postObj->FromUserName);
                    exit;
                }

                echo backMsgPic($word,$result['url'],$result['description'],$result['picture'],$postObj->ToUserName,$postObj->FromUserName);

            }else if(mb_strstr($word,'天气')){

                $url = "http://www.xunsearch.com/scws/api.php";
                $data['data'] = $word;
                $data['respond'] = 'json';

                $result = http_request($url,$data);
                $json = json_decode($result,true);
                $arr = [];
                foreach($json['words'] as $v){
                    if($v['attr'] == 'ns'){
                        $arr[]=$v['word'];
                    }
                }
                if([] == $arr){
                    echo  backMsgText('未知城市',$postObj->ToUserName,$postObj->FromUserName);
                }
                $content = '';

                foreach($arr as $v){
                    $key = "V35gWgKSicqNkYgngHcADggvl52A4fTB";
                    $url = "http://api.map.baidu.com/telematics/v3/weather?location={$v}&output=json&ak=".$key;
                    $result = http_request($url);
                    $json = json_decode($result,true);

                    if(0 == $json['error'] ){
                        $content .= "城市：".$v."\n日期：".$json['date']."\npm25:".$json['results'][0]['pm25']."\n气温:".$json['results'][0]['weather_data'][0]['temperature']."\n";
                    }

                }
                if('' == $content) echo backMsgText('请输入国内城市',$postObj->ToUserName,$postObj->FromUserName);

                echo backMsgText($content,$postObj->ToUserName,$postObj->FromUserName);
            }else if($type == 'image'){

                $url='https://api-cn.faceplusplus.com/facepp/v3/detect';
                $data['api_key'] = '5xAt9NFAzXwqCw5zGZu769fXO2k5uU14';
                $data['api_secret'] = 'JbWHijiJXD6dWOFIZX3PHLQeM28zfMSF';
                $data['image_url'] = $postObj->PicUrl;
                $data['return_attributes'] = 'age,beauty,gender';
                $result = http_request($url,$data);
                $json = json_decode($result,true);
                $attr = $json['faces'][0]['attributes'];
                $content = "测试结果\n年龄：".$attr['age']['value']."\n性别：";

                if($attr['gender']['value']=='Female'){
                    $content.= '女';
                }else{
                    $content.= "男";
                }

                if($attr['age']['value'] == 'Female'){
                    $content .= "\n颜值：". $attr['beauty']['female_score'];
                }else{
                    $content .="\n颜值：". $attr['beauty']['male_score'];
                }

                echo backMsgText($content,$postObj->ToUserName,$postObj->FromUserName);
            }else if($type == 'event'){
                echo $this->eventMsg();
            }else{
                //自定义回复
//
//                $result = Db::table('words')->where('word',$word)->find();
//                if(!$result){
//                    echo backMsgText('没听懂',$postObj->ToUserName,$postObj->FromUserName);
//                    exit;
//                }
//                $content = $result['back_word'];
//
//                echo  backMsgText($content,$postObj->ToUserName,$postObj->FromUserName);

                //图灵机器人接口实现回复

                $url = "http://openapi.tuling123.com/openapi/api/v2";
                $data = '{
                        "reqType":0,
                        "perception": {
                            "inputText": {
                                "text": "'.$word.'"
                            },
                            "inputImage": {
                                "url": "imageUrl"
                            },
                        },
                        "userInfo": {
                            "apiKey": "c264f4797da041398f8a99b834b8806f",
                            "userId": "asdgfasdgasdgasdf"
                        }
                    }';
                $result = http_request($url,$data);
                $json = json_decode($result,true);
                $arr = ['url'=>'','text'=>'','name'=>''];
                foreach($json['results'] as $v){
                    //回复链接时使用
                    if(isset($v['values']['url'])) $arr['url'] = $v['values']['url'];
                    if(isset($v['values']['text']))$arr['text'] = $v['values']['text'];
                    //回复新闻信息时使用
                    if(isset($v['values']['news'])){
                        $b = $v['values']['news'][0];
                        echo backMsgPic($b['name'],$b['detailurl'],$b['info'],$b['icon'],$postObj->ToUserName,$postObj->FromUserName);
                        exit;
                    }
                }

                if(!$arr['text']) echo  backMsgText('没听懂',$postObj->ToUserName,$postObj->FromUserName);
                $content = $arr['text'];

                if($arr['url'])  $content = "<a href='".$arr['url']." '>".$content."</a>";

                Db::insert();
                echo backMsgText($content,$postObj->ToUserName,$postObj->FromUserName);
            }
        }
    }


    //摇一摇游戏
    public function gamelist(){
        $ulist = Db::table('xsxb')->order('point desc')->select();
        $this->assign('list',$ulist);
        return $this->fetch();
    }

    //摇一摇AJAX获取数据
    public function getAjax(){
        $request = Request::instance();
        if(!$request->isAjax()) $this->error('非法登陆');
        $data = Db::table('xsxb')->select();
        $a=0;
        foreach($data as $v){
            if($v['point']>=100){
                $a=1;
            }
        }
        echo json_encode(['data'=>$data,'stop'=>$a]);
        exit;

    }

    //摇一摇录入数据
    public function ajaxGame(){
        $request = Request::instance();
        if(!$request->isAjax()) $this->error('非法登陆');
        $name = $request->param('name','');
        $num = $request->param('num','');

        if( ''!=$name && ''!=$num){
            Db::table('xsxb')->where('name',$name)->update(['point'=>$num]);
        }else if( ""!=$name && ''== $num){
            $result=Db::table('xsxb')->where('name',$name)->find();
            if($result){
                echo 1;
                exit;
            }
            Db::table('xsxb')->insert(['name'=>$name,'point'=>0]);
        }

    }

    //摇一摇用户端
    public function user(){
        $request = Request::instance();

        return $this->fetch();
    }

    public function bbbb(){
        $url = "http://php.net/manual/en/function.array-fill.php";
        echo $this->file_get_content($url);
    }


    public function file_get_content($url){
        $content = file_get_contents($url);
        return $content;
    }

}





//https请求方式的get和post方法。
function http_request($url,$data=null,$headers = null){

    // 初始化一个 cURL 对象
    $curl = curl_init();

    if(!empty($headers)){
        curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
    }

    // 设置你需要抓取的URL
    curl_setopt($curl, CURLOPT_URL,$url);

    //必须加这个，不加不好使（不多加解释，东西太多了）
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//对认证证书进行检验
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    if (!empty($data)){//post方式，否则是get方式
        //设置模拟post方式
        curl_setopt($curl,CURLOPT_POST,1);
        //传数据，get方式是直接在地址栏传的，这是post传参的解决方式
        curl_setopt($curl,CURLOPT_POSTFIELDS,$data);//$data可以是数组，json
    }

    // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。1是保存，0是输出
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    // 运行cURL，请求网页
    $output = curl_exec($curl);

    // 关闭URL请求
    curl_close($curl);

    return $output;
}
