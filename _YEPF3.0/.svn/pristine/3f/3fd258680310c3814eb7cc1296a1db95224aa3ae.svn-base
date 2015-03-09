<?php
/**
 * @name TidyFilter.class.php
 * @desc tidy 修复类
 * @author caoxd
 * @createtime 2008-11-18 10:29
 * @updatetime
 */
namespace ext;

class TidyFilter
{
    private $tidyConfig = array();
    
    /**
	 * @name __construct
	 * @desc 构造函数
	 * @param array $tidyConfig
	 * @return Object instance of tidyfilter
	 * @access public
	 *
	 */
    public function __construct($tidyConfig)
    {
        $this->setTidyConfig($tidyConfig);
    }
    
    /**
	 * @name repair
	 * @desc 补全修复文字中的html/xml代码
	 * @param string $html
	 * @param int $iconv default by 1
	 * @return object $mytidy
	 * @access public
	 *
	 */
    public function repair($html)
    {
    	if(!defined('YEPF_FORCE_CLOSE_ADDSLASHES') || YEPF_FORCE_CLOSE_ADDSLASHES !== true)
    	{
    		$html = stripslashes($html);
    	}
        $tidy = tidy_parse_string($html, $this->getTidyConfig(), 'UTF8');
        tidy_clean_repair($tidy);
        if(tidy_error_count($tidy)){
        	
            $html_info = explode(chr(0x0A), $html);
            $tidy_errors = explode(chr(0x0A), tidy_get_error_buffer($tidy));
            foreach($tidy_errors AS $message)
            {
                if(ereg('line ([0-9]*) column ([0-9]*) - Error: <(.*)> is not recognized!', $message, $result)){
                    //识别tag错误。假定错误可在当前行恢复。
                    $line = $result[1]-1;
                    $col = $result[2] - 1;
                    $tag = $result[3];
                    if($html_info[$line][$col] == '<'){
                        $html_info[$line] = substr(trim($html_info[$line]), 0, $col).'&lt;'.substr(trim($html_info[$line]), $col+1);
                    }
                    else{
                        continue;   
                    }
                }
            }
            $mytidy = tidy_parse_string(implode("\n",$html_info), $this->getTidyConfig(), 'UTF8');
            return $mytidy;
        }

        return $tidy;
    }
    
    
    /**
	 * @name setTidyConfig
	 * @desc 设置tidy配置
	 * @param array $tidyConfig
	 * @return void
	 * @access protected
	 *
	 */
    protected function setTidyConfig($tidyConfig)
    {
        $this->tidyConfig = $tidyConfig;
    }
    
     /**
	 * @name getTidyConfig
	 * @desc 取得tidy配置
	 * @param void
	 * @return array $tidyConfig
	 * @access protected
	 *
	 */
    protected function getTidyConfig()
    {
        return $this->tidyConfig;   
    }
}
?>