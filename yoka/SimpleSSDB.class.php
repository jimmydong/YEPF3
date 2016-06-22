<?php
namespace yoka;
/**
 * All methods(except *exists) returns false on error,
 * so one should use Identical(if($ret === false)) to test the return value.
 * 
 * Doc: http://ssdb.io/docs/php/
  
$ssdb = new SimpleSSDB('127.0.0.1', 8888);
$resp = $ssdb->set('key', '123');
$resp = $ssdb->get('key');
echo $resp; // output: 123
 
 */
class SimpleSSDB extends Ssdb
{
	function __construct($host, $port, $timeout_ms=2000){
		parent::__construct($host, $port, $timeout_ms);
		$this->easy();
	}
}

