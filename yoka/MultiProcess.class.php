<?php
namespace yoka;
/**
 * 使用http请求模拟PHP并发多线程
 * 原始代码自：http://www.cnblogs.com/niniwzw/archive/2010/01/30/1659589.html
 * @update by jimmy.dong@gmail.com 2012.4.12
 * 注意： 
 * 		中断后，HTTP抓取线程仍然继续进行。
 * 影响：
 * 		1，处理程序需注意重入问题。
 * 		2，设置公用存取区，可实现两次访问的异步延续
 */

class MultiProcess
{
    //要并行工作的url 列表
    private $urls = array();

    //curl 的选项
    private $options;
    private $connect_timeout	= 1;  			//秒, could be float (注意：测试中发现不能小于1)
    private $speed_timeout		= 60;   		//秒, should be integer  内网带宽限制暂时放大。上线时应调整
    
    //构造函数
    function __construct($options = array())
    {
        $this->setOptions($options);
    }

 
	/**
	 * 设置url 列表
	 * Enter description here ...
	 * @param array $urls
	 */
    function setUrls($urls)
    {
        $this->urls = $urls;
        return $this;
    }

    //设置选项
    /**
     * 配置CURL选项
     * 默认可不做处理。注意：_MS的参数，在低版本CURL上无效
     * @todo 针对每一个ch进行差异化设定 
     * @param unknown_type $options
     */
    function setOptions($options)
    {
        $options[CURLOPT_RETURNTRANSFER] = 1;
        if (isset($options['HTTP_POST'])) 
        {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $options['HTTP_POST']);
            unset($options['HTTP_POST']);
        }

        if (!isset($options[CURLOPT_USERAGENT])) 
        {
            $options[CURLOPT_USERAGENT] = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; MultiProcess;)';
        }

        if (!isset($options[CURLOPT_FOLLOWLOCATION])) 
        {
            $options[CURLOPT_FOLLOWLOCATION] = 1;
        }

        if (!isset($options[CURLOPT_HEADER]))
        {
            $options[CURLOPT_HEADER] = 0;
        }
/*       
        if (!isset($options[CURLOPT_CONNECTTIMEOUT_MS]))
        {
            $options[CURLOPT_CONNECTTIMEOUT_MS] = intval($this->connect_timeout * 1000);
        }

        if (!isset($options[CURLOPT_TIMEOUT_MS]))
        {
            $options[CURLOPT_TIMEOUT_MS] = intval(($this->connect_timeout + $this->speed_timeout + 0.1) * 1000);
        }
*/
         
        if (!isset($options[CURLOPT_LOW_SPEED_TIME]))
        {
            $options[CURLOPT_LOW_SPEED_TIME] = $this->speed_timeout;
        }
        
        $this->options = $options;
    }

	/**
	 * 开始进行多线程抓取。
	 * Enter description here ...
	 */
    function exec()
    {
        if(empty($this->urls) || !is_array($this->urls))
        {
            return false;
        }
        $curl = $data = array();
        $mh = curl_multi_init();
        foreach($this->urls as $k => $v)
        {
            $curl[$k] = $this->addHandle($mh, $v);
        }
        $this->execMulitHandle($mh);
        foreach($this->urls as $k => $v)
        {
        	if(!$curl[$k] || curl_errno($curl[$k])){
        		Debug::log('Error', curl_errno($curl[$k]) .':'. curl_error($curl[$k]), __FILE__.':'.__LINE__);
        		$data[$k] = '';
        	}else{
            	$data[$k] = curl_multi_getcontent($curl[$k]);
            	curl_multi_remove_handle($mh, $curl[$k]);
        	}
        }
        curl_multi_close($mh);
        return $data;
    }
    
    /**
     * 单进程抓取一个网页，用于测试Curl选项设置
     */
    function execOne($url)
    {
        if (empty($url)) {
            return false;
        }
        $ch = curl_init($url);
        $this->setOneOption($ch);
        $content = curl_exec($ch);
        if(curl_errno($ch)){
        	\yoka\Debug::log('Error', curl_error($ch));
        	return false;
        }
        curl_close($ch);
        return $content;
    }
    
    //内部函数，设置某个handle 的选项
    private function setOneOption($ch)
    {
    	\yoka\Debug::log('option', $this->options);
        curl_setopt_array($ch, $this->options);
    }

    //添加一个新的并行抓取 handle
    private function addHandle($mh, $url)
    {
        $ch = curl_init($url);
        $this->setOneOption($ch);
        curl_multi_add_handle($mh, $ch);
        return $ch;
    }

    //并行执行
    //改进： usleep减少服务器CPU消耗
    private function execMulitHandle($mh)
    {
        $running = null;
    	try{
	        do {
	            curl_multi_exec($mh, $running);
	            usleep(100000);
	        } while ($running > 0 && ($counter++ < $this->speed_timeout * 10));
	        \Debug::log('Multiprocess', $counter/10 . 's, remain ' . $running , __FILE__.':'.__LINE__);
    	}catch(\Exception $err){
			//should not be here    		
    	}
    }
}

