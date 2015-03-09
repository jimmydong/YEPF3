<?php
/**
 * @name Htmlfilter.class.php
 * @desc YEPF框架Html标签过滤类,可以指定需要过滤的标签和标签属性
 * @author 曹晓冬
 * @createtime 2008-11-18 10:27
 * @updatetime 2009-03-30 
 * @usage
 * 		$content = '测试文字' ;
 * 		$htmlfilter = new HtmlFilter('content'); //目前提供正文和回复两种类型
 *		$mytidy = $htmlfilter->repair($content);
 *		echo $htmlfilter->filter($mytidy);
 */
namespace ext;

if(!defined('YOKA')) exit('Illegal Request');

class HtmlFilter extends TidyFilter 
{
    private $allowTags = array();
    private $allowTagsConfig = array();
    private $allowTagsConfigValue = array();
    private $html;
    private $str;
    const ERROR_REPORTING = 6 ;
    
    /**
	 * @name __construct
	 * @desc 构造函数
	 * @param string $type 过滤的类型
	 * @return Object instance of htmlfilter
	 * @access public
	 */
    public function __construct($type = 'content')
    {
    	switch ($type)
    	{
    		case 'reply':
			//回复内容过滤
			$allowTags='br,img';
			$allowTagsConfig = array();
			$allowTagsConfig['img']='src,width,height,title,border,alt,style';
			$allowTagsConfigValue['img'] = array();
			$allowTagsConfigValue['img']['border'] = "0";
    		break;
    		default:
			/*---- html filter config begin ----*/
			//正文内容过滤
			$allowTags='br,p,hr,a,img,font,b,span,strong,table,tr,td,embed,em,u,div,tbody,i,s,ul,li,iframe';
			$allowTagsConfig=array();
			$allowTagsConfig['p']='align,style';
			$allowTagsConfig['a']='href,target,alt,style';
			$allowTagsConfig['img']='src,width,height,title,border,alt,style';
			$allowTagsConfig['font']='color,size,face,style';
			$allowTagsConfig['span']='style';
			$allowTagsConfig['embed']='width,src,height,type,pluginspage,loop,menu,play';
			$allowTagsConfig['div']='style,class';
			$allowTagsConfig['iframe']='width,src,height,allowtransparency,frameborder,scrolling';
			$allowTagsConfigValue = array();
			$allowTagsConfigValue['a'] = array();
			$allowTagsConfigValue['a']['target'] = "_blank";
			$allowTagsConfigValue['img'] = array();
			$allowTagsConfigValue['img']['border'] = "0";
			$allowTagsConfigValue['iframe']['src'] = array('htmlfilter','isYokaUrl');
			/*---- html filter config end ----*/
    		break;
    	}
    	
        if(!is_array($allowTags)){
            $allowTags = explode(",", $allowTags);
        }
        if(is_array($allowTags) && count($allowTags)){
            foreach($allowTags AS $tag)
            {
                $this->allowTags[] = strtolower($tag);
            }
        }
        if(is_array($allowTagsConfig) && count($allowTagsConfig)){
            foreach($allowTagsConfig AS $tagName => $config)
            {
                $this->allowTagsConfig[$tagName] = explode(",", $config);
            }
        }
        if(is_array($allowTagsConfigValue) && count($allowTagsConfigValue)){
            $this->allowTagsConfigValue = $allowTagsConfigValue;
        }
        $tidyConfig = array('force-output' => TRUE,'output-xhtml' => TRUE,'show-body-only' => TRUE,'uppercase-tags' => false,'markup' => TRUE,'preserve-entities' => TRUE,'wrap' => 0,'break-before-br' => FALSE,'doctype' => 'omit','show-errors' => self::ERROR_REPORTING ,'show-warnings' => FALSE);
        parent::__construct($tidyConfig);
    }
    
    /**
	 * @name filter
	 * @desc 过滤不能使用的html标签
	 * @param tidy object/string $tidy
	 * @return string $str
	 * @access public
	 *
	 */
    public function filter($tidy, $iconv = 1)
    {
        if(!is_object($tidy)){ //如果传入的是字符串，需要先得到tidy object再进行处理
            $tidy = tidy_parse_string($tidy, $this->getTidyConfig(), 'UTF8');
            tidy_clean_repair($tidy);
        }
        $body = tidy_get_body($tidy);
        $nodes = $body->child;
        $str = $this->checkTag($nodes);
        return $str;
    }
    /**
     * @name isYokaUrl
     * @desc 判断是否为yoka的url
     * @param string $url
     * @return bool
     * @access public
     */
    public static function isYokaUrl($url)
    {
    	return preg_match('/^http\:\/\/([a-zA-Z0-9])+\.yoka\.com\/(.+)/i', $url);
    }
    /**
	 * @name checkTag
	 * @desc 清除非允许html标签
	 * @param array $textNodes
	 * @return string $str
	 * @access private
	 *
	 */
    private function checkTag($textNodes)
    {
        $str = "";
        if(is_array($textNodes)){
            foreach($textNodes AS $node)
            {
            	//print_r($node);
                if($node->isText()){ //节点是文本，没有tag
                	//对空格需要特殊处理
                	//$tmp = str_replace("&nbsp;", " ", $node->value);
                	//$tmp1 = htmlspecialchars(substr($node->value, 0, -1), ENT_QUOTES);
                	//$tmp2 = str_replace(" ", "&nbsp;", $tmp1);
                    //$str .= substr($node->value, 0, -1);
                    $str .= $node->value;
                }
                else{ //节点中有tag，进行处理，如child中仍有tag，递归处理
                    $nodename = strtolower($node->name);
                    if(in_array($nodename, $this->allowTags)){ //当前tag是允许的tag,br不做处理
                    	if($nodename == 'br')
                    	{
                    		$str .= "<".$nodename."/>" ;
                    	}
                    	else 
                    	{
	                        $str .= "<".$nodename;
	                        if(is_array($node->attribute)){ //如果tag有属性，循环处理，判断是否在允许的属性之列
	                            foreach($node->attribute AS $a_name => $a_value)
	                            {
	                            	//对attribute无限制，或者是有限制但是该attribute在被许可的范围内
	                                if( !is_array($this->allowTagsConfig[$node->name]) || (is_array($this->allowTagsConfig[$node->name]) && in_array($a_name, $this->allowTagsConfig[$node->name])) ){
	                                    if(isset($this->allowTagsConfigValue[$node->name][$a_name])){
	                                    	$allowvaluedeal = $this->allowTagsConfigValue[$node->name][$a_name];
	                                        if(is_array($allowvaluedeal))//声明回调函数
	                                        {
	                                        	$tmp_value = '' ;
	                                        	if(true === method_exists($allowvaluedeal[0], $allowvaluedeal[1]))
	                                        	{
	                                        		if(call_user_func($allowvaluedeal, $a_value))
	                                        		{
	                                        			$tmp_value = $a_value;
	                                        		}
	                                        	}
	                                        	$a_value = $tmp_value;
	                                        }else //对于部分tag的指定属性的指定值进行赋值
	                                        {
	                                        	$a_value = $allowvaluedeal;
	                                        }
	                                    }
	                                    $str .= ' '.$a_name.'="'.$a_value.'"';
	                                }
	                            }
	                        }
	                        if(isset($this->allowTagsConfigValue[$node->name])){
	                            foreach($this->allowTagsConfigValue[$node->name] AS $a_name => $a_value)
	                            {
	                            	if(is_array($a_value)) continue;
	                                if(stripos($str, $a_name.'="'.$a_value.'"') === false){
	                                    $str .= ' '.$a_name.'="'.$a_value.'"';
	                                }
	                            }
	                        }
	                        $str .= ">";
	                        if(count($node->child) == 1 && $node->child[0]->isText()){ //只有一个child，且此child没有tag，直接赋值，不进入递归
	                            //$str .= htmlspecialchars(substr($node->child[0]->value, 0, -1), ENT_QUOTES);
	                            //$str .= substr($node->child[0]->value, 0, -1);
	                            $str .= $node->child[0]->value;
	                        }
	                        else{//进入递归
	                            $str .= $this->checkTag($node->child);
	                        }
	                        $str .= "</".$nodename.">";
                    	}
                        
                    }
                    else{
                        if(count($node->child) == 1 && $node->child[0]->isText()){ //只有一个child，且此child没有tag，直接赋值，不进入递归
                            //$str .= htmlspecialchars(substr($node->child[0]->value, 0, -1), ENT_QUOTES);
                            //$str .= substr($node->child[0]->value, 0, -1);
                            $str .= $node->child[0]->value;
                        }
                        else{//进入递归
                            $str .= $this->checkTag($node->child);
                        }
                    }
                }
            }
        }
        else{
            $str = $textNodes;
        }
        return $str;
    }
}
?>
