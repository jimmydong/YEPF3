<?php
/**
 * @name
 * @desc Cookie读取类
 */
namespace ext;
class Cookie {
	/**
	 * 获取cookie
	 *
	 * @param string $varname
	 * @return string | Boolean 不存在返回false；存在返回值
	 */
    static function getCookie($varname) {
        return isset($_COOKIE[$varname]) ? $_COOKIE[$varname] : false;
    }

    /**
     * 设置cookie
     *
     * @param string $varname
     * @param string $value
     * @param int $cookie_time cookie保存的时间。如果想保存1小时，请设该值为 time() + 60*60。
     * @param string $cookie_path
     * @param string $cookie_domain
     * @param boolean $secure 是否只能通过https协议访问
     * @param boolean $httponly 是否只能通过http协议读取cookie。值为true时，客户端的javascript不能读到该cookie。
     */
    static function setCookie($varname, $value, $cookie_time, $cookie_path, $cookie_domain, $secure, $httponly) {
        return setcookie($varname, $value, $cookie_time, $cookie_path, $cookie_domain, $secure, $httponly);
    }
}
?>