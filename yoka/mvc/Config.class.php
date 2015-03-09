<?php
namespace yoka\mvc;

class Config{

	private static $_instance = null;
	
	private $_config = null;
	private $_infiFile;
	
	private function __construct(){
		if (file_exists(ROOT_PATH.'/config.ini'))
			$this->_iniFile = ROOT_PATH.'/../config.ini';

		if (file_exists(ROOT_PATH.'/config.ini'))
			$this->_iniFile = ROOT_PATH.'/config.ini';

		if ($this->_iniFile)
			$this->_config = parse_ini_file($this->_iniFile, true);
	}
	
	public static function instance(){
		if ( self::$_instance == null ){
			 self::$_instance = new Config;
		}
		return self::$_instance;
	}

	
	public function getSection($section){
		return isset($this->_config[$section])? $this->_config[$section]:false;
	}
	
	public function getByName($name) {
		$config = parse_ini_file($this->_iniFile);
		return isset($config[$name])? $config[$name]:false;
	}
	
	
}
