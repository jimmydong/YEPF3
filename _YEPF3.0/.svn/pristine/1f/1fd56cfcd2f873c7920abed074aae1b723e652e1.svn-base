<?php
namespace yoka;

class YokaCookie {
	/**
	 * 包装Cookie::getCookie，便于cookie的统一处理
	 *
	 * @param string $key
	 * @return string | Boolean 不存在返回false；存在返回值
	 */
    public static function getCookie($key) {
    	return Cookie::getCookie($key);
    }

    /**
     * 包装Cookie::setCookie，便于cookie的统一处理
     *
     * @param string $varname
     * @param string $value
     * @param int $cookie_time cookie保存的时间。如果想保存1小时，请设该值为 time() + 60*60。
     * @param string $cookie_path
     * @param string $cookie_domain
     * @param boolean $secure 是否只能通过https协议访问。默认为false
     * @param boolean $httponly 是否只能通过http协议读取cookie。值为true时，客户端的javascript不能读到该cookie。默认为false。
     */
    static function setCookie($key, $value, $cookie_time = 0, $cookie_domain = '.yoka.com', $cookie_path = '/', $secure = false, $httponly = false) {
        return Cookie::setcookie($key, $value, $cookie_time, $cookie_path, $cookie_domain, $secure, $httponly);
    }
}
?>