<?php
/**
 +----------------------------------------------------------------------------
 * 合作网站帐号连接 控制器类
 +----------------------------------------------------------------------------
 * @author fanrong33
 * @version v1.0.0 Build 20141117
 +------------------------------------------------------------------------------
 */
class PartnerAction extends CommonAction {
	
    /**
     * 用户登录授权后的回调页面
     */
    public function callback_qq(){
    	$app_id 	  = '191170838';
    	$app_secrect  = 'edb016c8138364dccaa9e134f8223f06';
    	$callback_url = 'http://fanrong33.com/index.php/partner/callback_qq';
    	
    	if(isset($_GET['code']) && $_GET['code'] != ''){
    		import('@.ORG.OAuth.qq');
			$qq = new qqPHP($app_id, $app_secrect);
	    	$access_token = $qq->access_token($callback_url, $_GET['code']); // 获取access token
	    	
	        //用户登录授权后操作api
			$qq = new qqPHP($app_id, $app_secrect, $access_token['access_token']);
			$openid = $qq->get_openid(); // 登录用户uid
			Log::write('openid:'.$openid['openid'], Log::INFO);
			/** openid
			array(2) {
			  ["client_id"] => string(9) "101170838"
			  ["openid"] => string(32) "AB522CA1CFB4B20AF98F02F7F24B9A0D"
			}*/

			// QQ验证通过
			if(isset($openid) && isset($openid['openid'])){
				
				$user_info = $qq->get_user_info($openid['openid']);
				$weibo_info = $qq->get_info($openid['openid']);
				/** user_info
				array(18) {
				  ["ret"] => int(0)
				  ["msg"] => string(0) ""
				  ["is_lost"] => int(0)
				  ["nickname"] => string(12) "漫天凡心"
				  ["gender"] => string(3) "男"
				  ["province"] => string(6) "福建"
				  ["city"] => string(6) "厦门"
				  ["year"] => string(4) "1987"
				  ["figureurl"] => string(73) "http://qzapp.qlogo.cn/qzapp/101170838/AB522CA1CFB4B20AF98F02F7F24B9A0D/30"
				  ["figureurl_1"] => string(73) "http://qzapp.qlogo.cn/qzapp/101170838/AB522CA1CFB4B20AF98F02F7F24B9A0D/50"
				  ["figureurl_2"] => string(74) "http://qzapp.qlogo.cn/qzapp/101170838/AB522CA1CFB4B20AF98F02F7F24B9A0D/100"
				  ["figureurl_qq_1"] => string(69) "http://q.qlogo.cn/qqapp/101170838/AB522CA1CFB4B20AF98F02F7F24B9A0D/40"
				  ["figureurl_qq_2"] => string(70) "http://q.qlogo.cn/qqapp/101170838/AB522CA1CFB4B20AF98F02F7F24B9A0D/100"
				  ["is_yellow_vip"] => string(1) "0"
				  ["vip"] => string(1) "0"
				  ["yellow_vip_level"] => string(1) "0"
				  ["level"] => string(1) "0"
				  ["is_yellow_year_vip"] => string(1) "0"
				}*/
				/** weibo_info
				array(5) {
				  ["data"] => array(40) {
				    ["birth_day"] => int(25)
				    ["birth_month"] => int(1)
				    ["birth_year"] => int(1987)
				    ["city_code"] => string(1) "2"
				    ["comp"] => NULL
				    ["country_code"] => string(1) "1"
				    ["edu"] => NULL
				    ["email"] => string(0) ""
				    ["exp"] => int(10)
				    ["fansnum"] => int(0)
				    ["favnum"] => int(0)
				    ["head"] => string(0) ""
				    ["homecity_code"] => string(0) ""
				    ["homecountry_code"] => string(0) ""
				    ["homepage"] => string(0) ""
				    ["homeprovince_code"] => string(0) ""
				    ["hometown_code"] => string(0) ""
				    ["https_head"] => string(0) ""
				    ["idolnum"] => int(37)
				    ["industry_code"] => int(0)
				    ["introduction"] => string(0) ""
				    ["isent"] => int(0)
				    ["ismyblack"] => int(0)
				    ["ismyfans"] => int(0)
				    ["ismyidol"] => int(0)
				    ["isrealname"] => int(1)
				    ["isvip"] => int(0)
				    ["level"] => int(0)
				    ["location"] => string(20) "中国 福建 厦门"
				    ["mutual_fans_num"] => int(0)
				    ["name"] => string(9) "fanrong33"
				    ["nick"] => string(12) "漫天凡心"
				    ["openid"] => string(0) ""
				    ["province_code"] => string(2) "35"
				    ["regtime"] => int(1417447158)
				    ["send_private_flag"] => int(2)
				    ["sex"] => int(1)
				    ["tag"] => NULL
				    ["tweetnum"] => int(0)
				    ["verifyinfo"] => string(0) ""
				  }
				  ["errcode"] => int(0)
				  ["msg"] => string(2) "ok"
				  ["ret"] => int(0)
				  ["seqid"] => int(6087889990584376519)
				}
				 */
				
				// 判断绑定到该QQ的帐号是否存在？
				$user = D("User")->where(array('qq_uid'=>$openid['openid']))->find();
				if($user){
					// 1、存在绑定到该QQ的帐号
					//	1.1、已有帐号，直接登录
//						D("User")->setLoginData($user_id);
					$user_id = $user['id'];
				}else{
					// 2、不存在绑定到该QQ的帐号
					//  2.1、已存在登录用户，该用户（绑定 QQ 帐号，而不是登录！）
					if($this->_user){
						$user = D('User')->find($this->_user['id']);
						if($user['qq_uid']){
							exit('已绑定 QQ 帐号');
						}
						if(is_array($user_info) && $user_info['ret'] == 0){
							$data = array();
							$data['qq_uid']   = $openid['openid'];
							$data['qq_nickname']  = $user_info['nickname'];
							
							$effect = D('User')->where(array('id'=>$this->_user['id']))->save($data);
							$user_id = $this->_user['id'];
						}
					}else{
					// 	2.2、全新用户，使用QQ帐号自动创建帐号
						if(is_array($user_info) && $user_info['ret'] == 0){
							
			    			$data = array();
							$data['nickname'] = $user_info['nickname'];
							$data["gender"]   = $user_info["gender"];
							$data['qq_uid']   = $openid['openid'];
							$data['qq_nickname'] = $user_info['nickname'];
							$data['create_time'] = time();
							
							// 全新用户，添加新用户
							$user_id = D("User")->add($data);
							
							// 同步更新QQ开放平台头像到本地服务器
							//$picture_data = curl_get_contents($user_info["figureurl_2"]);
							//$json = $this->_dongxi->upload_avatar($user_id, base64_encode($picture_data));
						}
					}
				}
				$user = D('User')->find($user_id);
				
				$_SESSION['is_logined'] = true;
    			$_SESSION['user'] = $user;
			}
			
			redirect('/');
    	}
    }
	
	/**
	 * 用户登录授权后的回调页面（新浪微博）
	 */
	public function callback_weibo(){
		$app_id 	  = '4144625032';
		$app_secrect  = '91e9233e0af7e4ecda89398893c5c968';
		$callback_url = 'http://fanrong33.com/index.php/partner/callback_weibo';
		
		// 授权回调页面，即生成登录链接时的$callback_url
		if(isset($_GET['code']) && $_GET['code']!=''){
			
			import('@.ORG.OAuth.sina');
			$sina = new sinaPHP($app_id, $app_secrect);
			$access_token = $sina->access_token($callback_url, $_GET['code']); //获取access token
			/** access_token
			array(4) {
			  ["access_token"] 	=> string(32) "2.00uLeDpBmL9VFB867f07f3aa2PQKaD"
			  ["remind_in"] 	=> string(9) "157679999"
			  ["expires_in"] 	=> int(157679999)
			  ["uid"] 			=> string(10) "1670595450"
			}*/

			// 新浪微博验证通过
			if($access_token['access_token']){
				//用户登录授权后操作api
				$sina = new sinaPHP($app_id, $app_secrect, $access_token['access_token']);
				$user_info = $sina->get_user($access_token['uid']);
				/** user_info:
				array(48) {
				  ["id"] => int(1670595450)
				  ["idstr"] => string(10) "1670595450"
				  ["class"] => int(1)
				  ["screen_name"] => string(12) "漫天凡心"
				  ["name"] => string(12) "漫天凡心"
				  ["province"] => string(2) "35"
				  ["city"] => string(1) "2"
				  ["location"] => string(13) "福建 厦门"
				  ["description"] => string(27) "世上最怕莫过于坚持"
				  ["url"] => string(0) ""
				  ["profile_image_url"] => string(48) "http://tp3.sinaimg.cn/1670595450/50/5709774539/1"
				  ["cover_image_phone"] => string(77) "http://ww4.sinaimg.cn/crop.0.0.0.640.640/6ce2240djw1e9uwue857ij20hs0hsjuk.jpg"
				  ["profile_url"] => string(6) "solona"
				  ["domain"] => string(6) "solona"
				  ["weihao"] => string(0) ""
				  ["gender"] => string(1) "m"
				  ["followers_count"] => int(310)
				  ["friends_count"] => int(25)
				  ["pagefriends_count"] => int(1)
				  ["statuses_count"] => int(61)
				  ["favourites_count"] => int(61)
				  ["created_at"] => string(30) "Wed Dec 16 01:32:20 +0800 2009"
				  ["following"] => bool(false)
				  ["allow_all_act_msg"] => bool(false)
				  ["geo_enabled"] => bool(true)
				  ["verified"] => bool(false)
				  ["verified_type"] => int(-1)
				  ["remark"] => string(0) ""
				  ["status"] => array(20) {
				    ["created_at"] => string(30) "Tue Nov 11 23:32:50 +0800 2014"
				    ["id"] => int(3775865583914721)
				    ["mid"] => string(16) "3775865583914721"
				    ["idstr"] => string(16) "3775865583914721"
				    ["text"] => string(81) "//@HayinCai: [爱你][爱你][爱你]@漫天凡心 //@华强北商城:[羞嗒嗒]"
				    ["source_type"] => int(1)
				    ["source"] => string(63) "<a href="http://weibo.com/" rel="nofollow">微博 weibo.com</a>"
				    ["favorited"] => bool(false)
				    ["truncated"] => bool(false)
				    ["in_reply_to_status_id"] => string(0) ""
				    ["in_reply_to_user_id"] => string(0) ""
				    ["in_reply_to_screen_name"] => string(0) ""
				    ["pic_urls"] => array(0) {
				    }
				    ["geo"] => NULL
				    ["reposts_count"] => int(0)
				    ["comments_count"] => int(0)
				    ["attitudes_count"] => int(0)
				    ["mlevel"] => int(0)
				    ["visible"] => array(2) {
				      ["type"] => int(0)
				      ["list_id"] => int(0)
				    }
				    ["darwin_tags"] => array(0) {
				    }
				  }
				  ["ptype"] => int(0)
				  ["allow_all_comment"] => bool(false)
				  ["avatar_large"] => string(49) "http://tp3.sinaimg.cn/1670595450/180/5709774539/1"
				  ["avatar_hd"] => string(80) "http://ww2.sinaimg.cn/crop.0.0.640.640.1024/6393437ajw8elv9m573drj20hs0hsjs1.jpg"
				  ["verified_reason"] => string(0) ""
				  ["verified_trade"] => string(0) ""
				  ["verified_reason_url"] => string(0) ""
				  ["verified_source"] => string(0) ""
				  ["verified_source_url"] => string(0) ""
				  ["follow_me"] => bool(false)
				  ["online_status"] => int(0)
				  ["bi_followers_count"] => int(10)
				  ["lang"] => string(5) "zh-cn"
				  ["star"] => int(0)
				  ["mbtype"] => int(0)
				  ["mbrank"] => int(0)
				  ["block_word"] => int(0)
				  ["block_app"] => int(0)
				  ["credit_score"] => int(80)
				}*/
				
				// 判断绑定到该新浪微博的帐号是否存在？
				$user = D('User')->where(array('weibo_uid'=>$access_token['uid']))->find();
				if($user){
					// 存在绑定到该新浪微博的帐号，直接登录
					$user_id = $user['id'];
				}else{
					// 2、不存在绑定到该新浪微博的帐号
					//  2.1、已存在登录用户，该用户（绑定新浪微博帐号，而不是登录）
					if($this->_user){
						$user = D('User')->find($this->_user['id']);
						if($user['weibo_uid']){
							exit('已绑定新浪微博帐号');
						}
						$data = array();
						$data['weibo_uid'] = $user_info['id'];
						$data['weibo_nickname'] = $user_info['name'];
						
						$effect = D('User')->where(array('id'=>$this->_user['id']))->save($data);
						$user_id = $this->_user['id'];
					}else{
						//  2.2、不存在绑定到该新浪微博的帐号，使用新浪微博帐号自动创建帐号
						$data = array();
						$data['nickname'] 	= $user_info['name'];
						$data['gender']	  	= $user_info['gender']=='m' ? 2 : 1;
						$data['weibo_uid']	= $user_info['id'];
						$data['weibo_nickname'] = $user_info['name'];
						$data['create_time'] = time();
						
						$user_id = D('User')->add($data);
						
						// 同步更新weibo开放平台头像到本地服务器
						//$picture_data = curl_get_contents($user_info['avatar_hd']);
						//$json = $this->_dongxi->upload_avatar($user_id, base64_encode($picture_data));
					}
				}
				$user = D('User')->find($user_id);
				
				$_SESSION['is_logined'] = true;
    			$_SESSION['user'] = $user;
			}
			redirect('/');
		}
	}    
}