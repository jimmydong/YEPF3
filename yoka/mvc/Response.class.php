<?php
namespace yoka\mvc;

class Response {
    /**
     * 清空所有属性
     */
    public function clear(){
        $reflect = new \ReflectionClass($this);
        $list = $reflect->getProperties();
        foreach($list as $v){
            $t = $v->getName();
            unset($this->$t);
        }
        return $this;
    }
    /**
     * 别名
     */
    public function clean(){
        return $this->clear();
    }
    
    /**
     * 属性提取为数组
     */
    public function toArray(){
        $list = get_object_vars($this);
        foreach($list as $k=>$v){
            if(is_object($v)){
                $list[$k] = $this->_toArray($v);
            }
        }
        return $list;
    }
    
    /**
     * 辅助：实例对象转数组
     *
     */
    function _toArray ( &$item ) {
        if(is_object($item)){
            $clone = (array) $item;
            $rtn = [];
            $rtn['___SOURCE_KEYS_'] = $clone;
            
            while ( list ($key, $value) = each ($clone) ) {
                $aux = explode ("\0", $key);
                $newkey = $aux[count($aux)-1];
                $rtn[$newkey] = &$rtn['___SOURCE_KEYS_'][$key];
            }
            unset($rtn['___SOURCE_KEYS_']);
        }else{
            $rtn = $item;
        }
        //递归处理
        foreach($rtn as $k=>$v){
            if(is_object($v) || is_array($v)){
                $rtn[$k] = $this->_toAarray($v);
            }
        }
        return $rtn;
    }
}