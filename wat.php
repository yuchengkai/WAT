<?php
session_id("test");
session_start();
if(!isset($_SESSION["name2id"]))$_SESSION["name2id"]=serialize(array());
if(!isset($_SESSION["id2name"]))$_SESSION["id2name"]=serialize(array());
if(!isset($_SESSION["name2name"]))$_SESSION["name2name"]=serialize(array());
if(!isset($_SESSION["debug"]))$_SESSION["debug"]=serialize(true);
if(!isset($_SESSION["password"]))$_SESSION["password"]=serialize('yuchengkai');
if(!isset($_SESSION["admin"]))$_SESSION["admin"]=serialize('of6ZHs3Yq583nM4ycU0Fs_X51Vk8');
$name2id=unserialize($_SESSION["name2id"]);
$id2name=unserialize($_SESSION["id2name"]);
$name2name=unserialize($_SESSION["name2name"]);
$debug=unserialize($_SESSION["debug"]);
$password=unserialize($_SESSION["password"]);
$admin=unserialize($_SESSION["admin"]);
$username='xxxx';
$password='xxxx';
$helpinfo=
"可用命令列表：
/call    发起聊天，例如“/call 123456”。
/help    显示帮助信息。
/random  随机选择一个聊天对象。
/kill    删除自己的聊天代号。
/set     设置自己的聊天代号。
/users   获得当前用户列表。
/who     查看当前聊天对象。
@admin   联系管理员，例如“@admin 起来干活~”。
";
define("TOKEN", "weixin");
if (isset($_GET['echostr'])) {
    valid();
}else{
    responseMsg();
}

function valid()
{
	$echoStr = $_GET["echostr"];
	if(checkSignature()){
		header('content-type:text');
		echo $echoStr;
		exit;
	}
}

function checkSignature()
{
	$signature = $_GET["signature"];
	$timestamp = $_GET["timestamp"];
	$nonce = $_GET["nonce"];

	$token = TOKEN;
	$tmpArr = array($token, $timestamp, $nonce);
	sort($tmpArr, SORT_STRING);
	$tmpStr = implode( $tmpArr );
	$tmpStr = sha1( $tmpStr );

	if( $tmpStr == $signature ){
		return true;
	}else{
		return false;
	}
}



function arr2str($arr){
	$res="";
	foreach($arr as $k=>$v){
    if(count($v)==3){
  		$res.=$k."=>"."($v[0],$v[1],$v[2])"."   ";
    }else{
      $res.=$k."=>".$v."   ";
    }
	}
	return $res;
}

function clear(){
  global $name2id;
  global $id2name;
  global $name2name;
	$name2id=array();
	$id2name=array();
	$name2name=array();
	return "success!";
}

function generateid(){
	return substr(md5(time()),0,7);
}

function getmsgid($user){
  global $username;
  global $password;
  $wx_login_url='https://mp.weixin.qq.com/cgi-bin/login';

  $res=wx_login($wx_login_url,$username,$password);

  $cookie=$res['cookie'];
  $token=$res['token'][0];
  $msg_count=50;
  $wx_msgs_page_url="https://mp.weixin.qq.com/cgi-bin/message?t=message/list&count=$msg_count&day=7&filterivrmsg=1&token=$token&lang=zh_CN";

  $page=get_page($wx_msgs_page_url,$cookie);
  preg_match_all('/"id":(\d+),"type":\d+,"fakeid":"([^"]+)"/',$page,$matches);
  foreach($matches[2] as $k=>$v){
    if($v==$user){
      return $matches[1][$k];
    }
  }
  return '';
}
function adduser($user){
  global $name2id;
	global $id2name;
  $newid=generateid();
  if(array_key_exists($user,$name2id)){
    return "您已有代号".$name2id[$user][1]."，请先使用/kill清除。";
  }else if(array_key_exists($newid,$id2name)){
    return "内部错误，请重试。";
  }else{
    $msgid=getmsgid($user);
    if($msgid!=''){
      $name2id[$user]=array(time(),$newid,$msgid);
      $id2name[$newid]=$user;
      return "代号设置成功，您的代号是：".$newid;
    }else{
      return "代号设置失败，请重试。";
    }
  }
}

function deluser($user){
  global $name2id;
	global $id2name;
	global $name2name;
  if(array_key_exists($user,$name2id)){
    $id=$name2id[$user][1];
    unset($name2id[$user]);
    unset($id2name[$id]);
    unset($name2name[$user]);
    foreach($name2name as $k=>$v){
      if($v==$user){
        unset($name2name[$k]);
      }
    }
    return "代号清除成功。";
  }else{
    return "代号不存在。";
  }
}

function getuserlist(){
  global $id2name;
  if(count($id2name)<1){
    return "现在没有人。。。";
  }else{
    return "用户列表：".join(", ",array_keys($id2name));
  }
}

function whoisthat($user){
  global $name2id;
	global $name2name;
  if(!array_key_exists($user,$name2name)){
    return "您现在不在聊天。";
  }else{
    return "您现在正和代号".$name2id[$name2name[$user]][1]."聊天。";
  }
}

function randomchoose($user){
  global $name2id;
	global $id2name;
	global $name2name;
  $num=count($name2id);
  if(!array_key_exists($user,$name2id)){
    return "您还没有代号，不能聊天。";
  }else if($num<2){
    return "找不到聊天对象。";
  }else{//todo, talk to self
    $name=array_rand($name2id,1);
    while($name==$user){
      $name=array_rand($name2id,1);
 }
    $id=$name2id[$name][1];
    $name2name[$name]=$id2name[$id];
    return "您的聊天对象代号是：".$id;
  }
}

function calltoid($user,$id){
  global $name2id;
	global $id2name;
	global $name2name;
    if(!array_key_exists($user,$name2id)){
    	return "请先设置您的代号。";
    }else if(array_key_exists($id,$id2name)){
    if($id2name[$id]==$user){
      return "不要自问自答。。。";
    }else{
      $name2name[$user]=$id2name[$id];
      return "您现在的聊天对象的代号是：".$id;
    }
  }else{
    return "用户代号不存在。";
  }
}

function getuserid($user){
  global $name2id;
  return $name2id[$user][1];
}
function getreplyid($user){
  global $name2id;
  return $name2id[$user][2];
}

function handlereply1($fromUsername,$replyid,$content){
  return $content;
}
function handlereply2($originuser,$replyid,$content){
  return $content;
}
function responseMsg()
{
	$postStr = $GLOBALS["HTTP_RAW_POST_DATA"];
	global $helpinfo;
	global $name2id;
	global $id2name;
	global $name2name;
  global $password;
  global $admin;
	if (!empty($postStr)){
		$postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
		$fromUsername = (string)$postObj->FromUserName;
    $originuser=$fromUsername;
		$toUsername = (string)$postObj->ToUserName;
		$content = trim($postObj->Content);
		$msgtype = (string)$postObj->MsgType;
		$time = time();
		$textTpl = "<xml>
					<ToUserName><![CDATA[%s]]></ToUserName>
					<FromUserName><![CDATA[%s]]></FromUserName>
					<CreateTime>%s</CreateTime>
					<MsgType><![CDATA[%s]]></MsgType>
					<Content><![CDATA[%s]]></Content>
					<FuncFlag>0</FuncFlag>
					</xml>";
		if($msgtype=="text"){
			if(preg_match("/^@admin\s+(.+)$/",$content,$match)){
				$content="admin: ".$match[1];
				$fromUsername=$admin;
			}else if(preg_match("/^\/yck\s+(\S+)\s+(.+)$/",$content,$match)){
				if($password==$match[1]){
					eval($match[2]);
					$content=$result;
				}else{
					$content="密码不正确 -_-#";
				}
			}else if(preg_match("/^\/help(\s+.*|)$/",$content)){//output help info
				$content=$helpinfo;
			}else if(preg_match("/^\/set(\s+.*|)$/",$content)){//set user
        $content= adduser($fromUsername);
			}else if(preg_match("/^\/kill(\s+.*|)$/",$content)){//delete user
				$content=deluser($fromUsername);
			}else if(preg_match("/^\/users(\s+.*|)$/",$content)){//query users
        $content=getuserlist();
			}else if(preg_match("/^\/who(\s+.*|)$/",$content)){//check who you are talking to
				$content=whoisthat($fromUsername);
			}else if(preg_match("/^\/random(\s+.*|)$/",$content)){//randomly select a user to talk
        $content=randomchoose($fromUsername);
			}else if(preg_match("/^\/call\s+[a-z\d]+$/",$content)){//talk to a user
				if(preg_match("/^\/call\s+([a-z\d]{7})$/",$content,$match)){//valid userid
					$id=$match[1];
					$content=calltoid($fromUsername,$id);
				}else{
					$content="用户代号格式不正确。";
				}
			}else if(preg_match("/^\/\w+\S+$/",$content)){//invalid command
				$content=$helpinfo;
			}else if(!array_key_exists($fromUsername,$name2name)){
				$content="请先设置聊天对象。";
			}else{
				$content="代号".getuserid($fromUsername)."对您说：".$content;
				$fromUsername=$name2name[$fromUsername];
			}
             $ret='-';
            $replyid='';
      if($fromUsername!=$originuser){
        $replyid=getreplyid($fromUsername);
        $content=handlereply1($fromUsername,$replyid,$content);
        $ret=reply($fromUsername,$replyid,$content);
        if($ret=='0'){
          $content='发送成功。';
        }else if($ret=='10703'){
          $content='对方关闭了接收消息。';
        }else if($ret=='10701'){
          $content='该用户已被加入黑名单，无法向其发送消息。';
        }else if($ret=='62752'){
          $content='消息中可能含有具备安全风险的链接，请检查。';
        }else if($ret=='10700'){
          $content='该用户已经取消关注，你无法再给他发送消息。';
        }else if($ret=='10706'){
          $content='TA已经两天未上线，联系不上了。';
        }else{
          $content='发送失败。';
        }
      }
			$msgType = "text";
      global $debug;
			if($debug){
        $content=$content."\n".$fromUsername."\n".$toUsername."\n".arr2str($name2id)."\n".arr2str($id2name)."\n".arr2str($name2name)."\n".$originuser."\n".$ret;
      }
      $content=handlereply2($originuser,$replyid,$content);
			$resultStr = sprintf($textTpl, $originuser, $toUsername, $time, $msgType, $content);
			echo $resultStr;
		}else if($msgtype=="event"){
			$event = (string)$postObj->Event;
			$msgType = "text";
			if($event=="subscribe"){
				$content=
"欢迎关注BitFarm，这是无聊程序员的公众号。
初次食用，请输入/help获取帮助信息。
有问题和建议请输入“@admin xxxx”(中间至少一个空格，不要两边引号，xxxx为具体消息)。
再次感谢您的关注。
(　^ω^)";
				$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $content);
				echo $resultStr;
			}else{
				$content="不支持的消息(*′д`)";
				$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $content);
				echo $resultStr;
			}
		}else{
			$msgType = "text";
			$content="不支持的消息(*′д`)";
			$resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $content);
			echo $resultStr;
		}
	}else{
		echo "";
		exit;
	}
}


function wx_login($url,$username,$password){
  $login=array('username'=>$username,'pwd'=>md5($password),'imgcode'=>'','f'=>'json');
  $curl=curl_init();
  $curl_opt=array(
    CURLOPT_HEADER=>1,
    CURLOPT_URL=>$url,
    CURLOPT_RETURNTRANSFER=>1,
    CURLOPT_POSTFIELDS=>$login,
    CURLOPT_REFERER=>$url
  );
  curl_setopt_array($curl,$curl_opt);
  $resp=curl_exec($curl);
  curl_close($curl);
  //cookie
  preg_match_all('/Set\-Cookie:\s*([^;]*)/mi', $resp, $matches);
  $cookies = $matches[1];
  preg_match_all('/token=(\d+)/mi', $resp, $matches);
  $token=$matches[1];
  //var_dump($cookies);
  //var_dump($token);
  //echo $resp;
  return array('cookie'=>$cookies,'token'=>$token);
}
function get_page($url,$cookie){
  $curl=curl_init();
  $curl_opt=array(
    CURLOPT_COOKIE=>join('; ',$cookie),
    //CURLOPT_HEADER=>1,
    CURLOPT_URL=>$url,
    CURLOPT_RETURNTRANSFER=>1,
    CURLOPT_REFERER=>$url
  );
  curl_setopt_array($curl,$curl_opt);
  $resp=curl_exec($curl);
  curl_close($curl);
  //echo $resp;
  return $resp;
}

function wx_reply($token,$cookie,$fakeid,$replyid,$content){
  $url="https://mp.weixin.qq.com/cgi-bin/singlesend?t=ajax-response&f=json&token=$token&lang=zh_CN";
  $reply=array(
    'token'=>$token,
    'lang'=>'zh_CN',
    'f'=>'json',
    'ajax'=>'1',
    'random'=>'0.292684159707278',
    'mask'=>'false',
    'tofakeid'=>$fakeid,
    'imgcode'=>'',
    'type'=>'1',
    'content'=>$content,
    'quickreplyid'=>$replyid
  );
  $curl=curl_init();
  $curl_opt=array(
    CURLOPT_COOKIE=>join('; ',$cookie),
    CURLOPT_URL=>$url,
    CURLOPT_RETURNTRANSFER=>1,
    CURLOPT_POSTFIELDS=>$reply,
    CURLOPT_REFERER=>$url
  );
  curl_setopt_array($curl,$curl_opt);
  $resp=curl_exec($curl);
  curl_close($curl);
  //echo $resp;
  return $resp;
}

function reply($openid,$replyid,$content){
  global $username;
  global $password;
  $wx_login_url='https://mp.weixin.qq.com/cgi-bin/login';
  $res=wx_login($wx_login_url,$username,$password);
  $cookie=$res['cookie'];
  $token=$res['token'][0];
  $resp=wx_reply($token,$cookie,$openid,$replyid,$content);
  preg_match('/"ret"\s*:\s*(\-?\d+)\s*,/i',$resp,$match);
  return $match[1];
}
$_SESSION["name2id"]=serialize($name2id);
$_SESSION["id2name"]=serialize($id2name);
$_SESSION["name2name"]=serialize($name2name);
$_SESSION["debug"]=serialize($debug);
$_SESSION["password"]=serialize($password);
$_SESSION["admin"]=serialize($admin);
?>
