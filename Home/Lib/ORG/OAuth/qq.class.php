<?php
/**
 * PHP Library for qq.com
 * http://connect.qq.com
 * 
 * @author fanrong33 (http://fanrong33.com)
 */
class qqPHP
{
	function __construct($appid, $appkey, $access_token=NULL){
		$this->appid=$appid;
		$this->appkey=$appkey;
		$this->access_token=$access_token;
	}

	function login_url($callback_url){
		$params=array(
			'client_id'=>$this->appid,
			'redirect_uri'=>$callback_url,
			'response_type'=>'code',
			'scope'=>'get_user_info,get_info'
		);
		return 'https://graph.qq.com/oauth2.0/authorize?'.http_build_query($params);
	}

	function access_token($callback_url, $code){
		$params=array(
			'grant_type'=>'authorization_code',
			'client_id'=>$this->appid,
			'client_secret'=>$this->appkey,
			'code'=>$code,
			'state'=>'',
			'redirect_uri'=>$callback_url
		);
		$url='https://graph.qq.com/oauth2.0/token?'.http_build_query($params);
		$result_str=$this->http($url);
		$json_r=array();
		if($result_str!='')parse_str($result_str, $json_r);
		return $json_r;
	}

	/**
	function access_token_refresh($refresh_token){
	}
	**/

	function get_openid(){
		$params=array(
			'access_token'=>$this->access_token
		);
		$url='https://graph.qq.com/oauth2.0/me?'.http_build_query($params);
		$result_str=$this->http($url);
		$json_r=array();
		if($result_str!=''){
			preg_match('/callback\(\s+(.*?)\s+\)/i', $result_str, $result_a);
			$json_r=json_decode($result_a[1], true);
		}
		return $json_r;
	}

	function get_user_info($openid){
		$params=array(
			'oauth_consumer_key'=>$this->appid,
			'openid'=>$openid,
			'format'=>'json'
		);
		$url='https://graph.qq.com/user/get_user_info';
		return $this->api($url, $params);
	}
	
	function get_info($openid){
		$params=array(
			'oauth_consumer_key'=>$this->appid,
			'openid'=>$openid,
			'format'=>'json'
		);
		$url='https://graph.qq.com/user/get_info';
		return $this->api($url, $params);
	}

	function api($url, $params, $method='GET'){
		$params['access_token']=$this->access_token;
		if($method=='GET'){
			$result_str=$this->http($url.'?'.http_build_query($params));
		}else{
			$result_str=$this->http($url, http_build_query($params), 'POST');
		}
		$result=array();
		if($result_str!='')$result=json_decode($result_str, true);
		return $result;
	}

	function http($url, $postfields='', $method='GET', $headers=array()){
		$ci=curl_init();
		curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); 
		curl_setopt($ci, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ci, CURLOPT_TIMEOUT, 30);
		if($method=='POST'){
			curl_setopt($ci, CURLOPT_POST, TRUE);
			if($postfields!='')curl_setopt($ci, CURLOPT_POSTFIELDS, $postfields);
		}
		curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ci, CURLOPT_URL, $url);
		$response=curl_exec($ci);
		curl_close($ci);
		return $response;
	}
}