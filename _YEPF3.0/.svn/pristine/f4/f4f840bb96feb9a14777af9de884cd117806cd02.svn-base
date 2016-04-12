<?php
/**
 * @name User.class.php
 * @desc 用户操作类
 * @author caoxd
 * @createtime 2009-02-24 11:59
 * @updatetime
 * @usage 
 * list($UID, $USERNAME) = User::getMyUidCN()
 */
namespace yoka;

if (! defined ( 'YOKA' ))
	exit ( 'Illegal Request' );


class User {
	/**
	 * @name getMyUidCN
	 * @desc 获得当前登录人的ID和CN
	 * @return array  0为uid,1为cn
	 */
	public function getMyUidCN() {
		$cookie = isset ( $_COOKIE ['KM_PASSPORT_MEMBER'] ) ? $_COOKIE ['KM_PASSPORT_MEMBER'] : '';
		if (empty ( $cookie ))
			return array ('', '' );
		$array = explode ( "&", $cookie );
		$info = array ();
		foreach ( $array as $v ) {
			list ( $key, $value ) = explode ( '=', $v );
			$info [$key] = $value;
		}
		//支持sign和sign2 如果不需要sign了，可以删除
		$sign = md5 ( substr ( md5 ( $info ['pwd'] ), 0, 20 ) . 'YoKa' . substr ( md5 ( $info ['id'] ), 10, 20 ) );
		$sign2 = md5 ( substr ( md5 ( $info ['pwd'] ), 0, 20 ) . 'YoKa' . substr ( md5 ( $info ['id'] . $info ['uid'] . $info ['guid'] ), 10, 20 ) );
		if ($info ['uid'] > 0 && (@$sign == @$info ['sign'] || $sign2 == @$info ['sign2'])) {
			return array ($info ['uid'], $info ['id'] );
		}
		return array ('', '' );
	}
	
	/**
	 * 获取当前登录用户信息，适合在需要读取用户登录状态的页面中，通常放在init,php中调用，如：
	$current_user = User::getCurrentLoginUser();
	if ($current_user && is_array($current_user))
	{
		extract($current_user);
	}
	 * @author wangyi yz124s@hotmail.com
	 * @return Array 用户信息，[loginuser_uid,loginuser_name,loginuser_avatar]
	 */
	public function getCurrentLoginUser() {
		$cookiename = 'KM_PASSPORT_MEMBER';
		$cookie_str = $_COOKIE [$cookiename];
		if (! empty ( $cookie_str )) { // cookie存在
			

			$array = explode ( "&", $cookie_str );
			$info = array ();
			foreach ( $array as $v ) {
				list ( $key, $value ) = explode ( '=', $v );
				$info [$key] = $value;
			}
			//支持sign和sign2 如果不需要sign了，可以删除
			$sign = md5 ( substr ( md5 ( $info ['pwd'] ), 0, 20 ) . 'YoKa' . substr ( md5 ( $info ['id'] ), 10, 20 ) );
			$sign2 = md5 ( substr ( md5 ( $info ['pwd'] ), 0, 20 ) . 'YoKa' . substr ( md5 ( $info ['id'] . $info ['uid'] . $info ['guid'] ), 10, 20 ) );
			if ($info ['uid'] > 0 && (@$sign == @$info ['sign'] || $sign2 == @$info ['sign2'])) {
				$user = array ('loginuser_uid' => $info ['uid'], 'loginuser_name' => $info ['id'], 'loginuser_avatar' => $info ['avatar_url'], 'email' => $info ['email'] );
				return $user;
			}
		}
		
		return null;
	}

}
?>