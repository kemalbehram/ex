<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/8/23
 * Time: 11:31
 */

$auth = $_SERVER['HTTP_AUTHORIZATION'];
$open=fopen("log.txt","a+" );
fwrite($open,json_encode($_SERVER)."\r\n");
if(empty($auth)){
    $ret = array(
        'code' => 1,
        'message' => 'token invalid',
    );
    die(json_encode($ret));
}else{
    $arr = explode('|', $auth);
    $token = $arr[0];
    if (count($arr) == 1){
        $plat = 'ios';
    }else{
        $plat = $arr[1];
    }

    if(isset($_SERVER['HTTPS']) && ('1' == $_SERVER['HTTPS'] || 'on' == strtolower($_SERVER['HTTPS']))){
        $http = 'https://';
    }elseif(isset($_SERVER['SERVER_PORT']) && ('443' == $_SERVER['SERVER_PORT'] )) {
        $http = 'https://';
    }else{
        $http = 'http://';
    }
    fwrite($open,$http."\r\n");
    if ($plat == 'web'){
        $host = $http . $_SERVER["HTTP_HOST"] . "/api/user/user-info";
    }else{
        $host = $http . $_SERVER["HTTP_HOST"] . "/api/member/info";
    }
    fwrite($open,$host."\r\n");
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $host);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    $httpHeader[] = 'Content-Type:application/x-www-form-urlencoded';
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['access_token'=>$token]) );
    curl_setopt($ch, CURLOPT_HTTPHEADER, $httpHeader);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false); //处理http证书问题
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $ret = curl_exec($ch);
    if (false === $ret) {
        $ret =  curl_errno($ch);
    }
    curl_close($ch);
    $ret = json_decode($ret);
    if($ret->code == 200){
		//$open=fopen("log.txt","a+" );
		fwrite($open,json_encode($ret->data)."\r\n");
		fclose($open);
		
		$user_id = $plat == 'web' ? $ret->data->UID : $ret->data->id;
        $data = array(
            'code' => 0,
            'message' => 'success',
            'data' => ['user_id'=>intval($user_id)]
        );
        die(json_encode($data));
    }else{
        $data = array(
            'code' => 1,
            'message' => $ret->message,
        );
        die(json_encode($data));
    }
}
