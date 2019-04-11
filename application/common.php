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
// 
/*
$str='作者：www.phpernote.com';
$key='123456';
$encrypt=passport_encrypt($str,$key);
$decrypt=passport_decrypt($encrypt,$key);

echo '原文：',$str."\n";
echo '密文：',$encrypt."\n";
echo '译文：',$decrypt."\n";
*/

/*
*功能：对字符串进行加密处理
*参数一：需要加密的内容
*参数二：密钥
*/
function passport_encrypt($str,$key){ //加密函数
	srand((double)microtime() * 1000000);
	$encrypt_key=md5(rand(0, 32000));
	$ctr=0;
	$tmp='';
	for($i=0;$i<strlen($str);$i++){
		$ctr=$ctr==strlen($encrypt_key)?0:$ctr;
		$tmp.=$encrypt_key[$ctr].($str[$i] ^ $encrypt_key[$ctr++]);
	}
	return base64_encode(passport_key($tmp,$key));
}

/*
*功能：对字符串进行解密处理
*参数一：需要解密的密文
*参数二：密钥
*/
function passport_decrypt($str,$key){ //解密函数
	$str=passport_key(base64_decode($str),$key);
	$tmp='';
	for($i=0;$i<strlen($str);$i++){
		$md5=$str[$i];
		$tmp.=$str[++$i] ^ $md5;
	}
	return $tmp;
}

/*
*辅助函数
*/
function passport_key($str,$encrypt_key){
	$encrypt_key=md5($encrypt_key);
	$ctr=0;
	$tmp='';
	for($i=0;$i<strlen($str);$i++){
		$ctr=$ctr==strlen($encrypt_key)?0:$ctr;
		$tmp.=$str[$i] ^ $encrypt_key[$ctr++];
	}
	return $tmp;
}

function getapkurl($dir,$regx){
	$d = dir($dir);
	$file = '';
	while (false !== ($entry = $d->read())) {
	    if($entry != '.' && $entry != '..' && is_file($dir.'/'.$entry)){
	    	if(preg_match("#{$regx}#",$entry,$match) && isset($match[1])){
	    		if(!$file || isbigversion($match[1],$file)){
	    			$file = $match[1];
	    		}
	    	}
	    }
	}
	return $file?array('name'=>$file.'.apk','version'=>$file):array();
}


// 如果返回true ，ruguo f>s, fanhui true   1.2.3
function isbigversion($f,$s){
	$farr = explode('.',$f);
	$sarr = explode('.',$s);
	$is = false;
	if($farr[0] > $sarr[0]){
		$is = true;
	}else if($farr[1]>$sarr[1]){
		$is = true;
	}else if($farr[2]>$sarr[2]){
		$is = true;
	}
	return $is;
}