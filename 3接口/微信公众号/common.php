<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

function logs($info){
    $str="您记录的内容是:".$info."\n当前访问者ip是:".
        $_SERVER["REMOTE_ADDR"]."\n您记录的时间是:".
        date('Y-m-d H:i:s')."\n\n";
    file_put_contents(ROOTPATH."/log/log.txt",$str, FILE_APPEND);
}


//文本回复
function backMsgText($content='',$FromUserName='gh_a1e8db111675',$ToUserName="oFW241L9JHsII9NXwZR9IMKa0C64"){
    if('' != $content){
        $xml = "
            <xml>
                <ToUserName><![CDATA[{$ToUserName}]]></ToUserName>
                <FromUserName><![CDATA[{$FromUserName}]]></FromUserName>
                <CreateTime>{time()}</CreateTime>
                <MsgType><![CDATA[text]]></MsgType>
                <Content><![CDATA[{$content}]]></Content>
             </xml>";
        return  $xml;
    }
}
//图文回复
function backMsgPic($game_name='',$url='',$description='',$picture='',$FromUserName='gh_a1e8db111675',$ToUserName="oFW241L9JHsII9NXwZR9IMKa0C64"){
    if($url && $game_name){
        $xml = "
        <xml>
          <ToUserName><![CDATA[$ToUserName]]></ToUserName>
          <FromUserName><![CDATA[$FromUserName]]></FromUserName>
          <CreateTime>{time()}</CreateTime>
          <MsgType><![CDATA[news]]></MsgType>
          <ArticleCount>1</ArticleCount>
          <Articles>
            <item>
              <Title><![CDATA[{$game_name}]]></Title>
              <Description><![CDATA[{$description}]]></Description>
              <PicUrl><![CDATA[{$picture}]]></PicUrl>
              <Url><![CDATA[{$url}]]></Url>
            </item>
          </Articles>
        </xml>
        ";
        return $xml;
    }
}
//音乐回复
function backMsgMusic($music='',$url='',$burl='',$description='',$FromUserName='gh_a1e8db111675',$ToUserName="oFW241L9JHsII9NXwZR9IMKa0C64"){
    if('' != $music && $url != '' && $burl!= ''){
        $xml = "
        <xml>
           <ToUserName><![CDATA[$ToUserName]]></ToUserName>
          <FromUserName><![CDATA[$FromUserName]]></FromUserName>
          <CreateTime>{time()}</CreateTime>
          <MsgType><![CDATA[music]]></MsgType>
          <Music>
            <Title><![CDATA[$music]]></Title>
            <Description><![CDATA[$description]]></Description>
            <MusicUrl><![CDATA[$url]]></MusicUrl>
            <HQMusicUrl><![CDATA[$burl]]></HQMusicUrl>
          </Music>
        </xml>
        ";
        return $xml;
    }
}

