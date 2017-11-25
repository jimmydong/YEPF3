<?php
namespace yoka;
/**
 * 使用页面调试
 * by jimmy.dong@gmail.com
 * 
 * 前置条件：
 * 		1, composer.json 增加： "maximebf/debugbar": "1.*"
 * 		2, 将BarDebug.tar.gz解压至HTTP目录
 * 		3, 页头加载：
			<link rel="stylesheet" type="text/css" href="/Resources/vendor/font-awesome/css/font-awesome.min.css">
			<link rel="stylesheet" type="text/css" href="/Resources/vendor/highlightjs/styles/github.css">
			<link rel="stylesheet" type="text/css" href="/Resources/debugbar.css">
			<link rel="stylesheet" type="text/css" href="/Resources/widgets.css">
			<link rel="stylesheet" type="text/css" href="/Resources/openhandler.css">
			<script type="text/javascript" src="/Resources/vendor/jquery/dist/jquery.min.js"></script>
			<script type="text/javascript" src="/Resources/vendor/highlightjs/highlight.pack.js"></script>
			<script type="text/javascript" src="/Resources/debugbar.js"></script>
			<script type="text/javascript" src="/Resources/widgets.js"></script>
			<script type="text/javascript" src="/Resources/openhandler.js"></script>
			<script type="text/javascript">jQuery.noConflict(true);</script>
 * 
 * 使用方法： 
 * 		A，建议方案： 结合YEPF Debug（参见Debug相关注释）
 * 		B，手工方式： 
	    	$debugbar = new \BarDebug();
	    	$debugbarRenderer = $debugbar->getJavascriptRenderer("/Resources");
	    	$debugbar->getCollector('Costom Log')->add('this is title', ['this','is','content']);
	    	echo $debugbarRenderer->render();
 */
class BarDebug extends \DebugBar\DebugBar
{
	public function __construct()
	{
		$this->addCollector(new \DebugBar\DataCollector\PhpInfoCollector());
		$this->addCollector(new \DebugBar\DataCollector\MemoryCollector());
		$this->addCollector(new TimeCollector());
		$this->addCollector(new YepfCollector());
		$this->addCollector(new DbCollector());
		$this->addCollector(new CacheCollector());
	}
}

class YepfCollector extends \DebugBar\DataCollector\DataCollector implements \DebugBar\DataCollector\Renderable, \DebugBar\DataCollector\AssetProvider
{
	protected $name = 'Costom Log';
	protected $data = [];

	public function add($title, $value, $caller){
		$this->data[] = [$title, $value, $caller];
	}
	public function collect()
	{
		$data = array();
		foreach ($this->data as $k => $v) {
			$data[($k+1).' '.$v[0].'|-|'.$v[2]] = $this->getVarDumper()->renderVar($v[1]);
		}
		return 	array('counter' => count($this->data), 'table' => $data);
	}
	public function getName()
	{
		return $this->name;
	}
	public function getAssets() {
		return $this->getVarDumper()->getAssets();
	}
	public function getWidgets()
	{
		$name = $this->getName();
		$widget = "PhpDebugBar.Widgets.HtmlVariableListWidget";
		return array(
				"{$name}" => array(
						"icon" => "gear",
						"widget" => $widget,
						"map" => "{$name}.table",
						"default" => "{}"
				),
				"{$name}:badge" => array(
						"map" => "{$name}.counter",
						"default" => 0
				)
		);
	}
}
class DbCollector extends \DebugBar\DataCollector\DataCollector implements \DebugBar\DataCollector\Renderable, \DebugBar\DataCollector\AssetProvider
{
	protected $name = 'DB Log';
	protected $data = [];
	
	public function add($title, $value, $caller){
		$this->data[] = [$title, $value, $caller];
	}
	public function collect()
	{
		$data = array();
		foreach ($this->data as $k => $v) {
			$data[($k+1).' '.$v[0].'|-|'.$v[2]] = $this->getVarDumper()->renderVar($v[1]);
		}
		return 	array('counter' => count($this->data), 'table' => $data);
	}
	public function getName()
	{
		return $this->name;
	}
	public function getAssets() {
		return $this->getVarDumper()->getAssets();
	}
	public function getWidgets()
	{
		$name = $this->getName();
		$widget = "PhpDebugBar.Widgets.HtmlVariableListWidget";
		if(count($this->data)){
			return array(
					"{$name}" => array(
							"icon" => "gear",
							"widget" => $widget,
							"map" => "{$name}.table",
							"default" => "{}"
					),
					"{$name}:badge" => array(
							"map" => "{$name}.counter",
							"default" => 0
					)
			);
		}else{
			return array();
		}
	}
}
class CacheCollector extends \DebugBar\DataCollector\DataCollector implements \DebugBar\DataCollector\Renderable, \DebugBar\DataCollector\AssetProvider
{
	protected $name = 'Cache Log';
	protected $data = [];
	
	public function add($title, $value, $caller){
		$this->data[] = [$title, $value, $caller];
	}
	public function collect()
	{
		$data = array();
		foreach ($this->data as $k => $v) {
			$data[($k+1).' '.$v[0].'|-|'.$v[2]] = $this->getVarDumper()->renderVar($v[1]);
		}
		return 	array('counter' => count($this->data), 'table' => $data);
	}
	public function getName()
	{
		return $this->name;
	}
	public function getAssets() {
		return $this->getVarDumper()->getAssets();
	}
	public function getWidgets()
	{
		$name = $this->getName();
		$widget = "PhpDebugBar.Widgets.HtmlVariableListWidget";
		if(count($this->data)){
			return array(
					"{$name}" => array(
							"icon" => "gear",
							"widget" => $widget,
							"map" => "{$name}.table",
							"default" => "{}"
									),
									"{$name}:badge" => array(
											"map" => "{$name}.counter",
											"default" => 0
									)
									);
		}else{
			return array();
		}
	}
}
class TimeCollector extends \DebugBar\DataCollector\DataCollector implements \DebugBar\DataCollector\Renderable
{
	protected $requestStartTime;
	protected $requestEndTime;
	protected $data = [];
	
	public function __construct(){
		$this->requestStartTime = (float) microtime(true);
	}
	public function add($title, $value, $caller){
		$this->data[] = [$title, $value .' '. $caller];
	}
	public function collect()
	{
		$this->requestEndTime = microtime(true);
		$data = array();
		foreach ($this->data as $k => $v) {
			$data[($k+1).' '.$v[0].'|-|'.$v[2]] = $this->getVarDumper()->renderVar($v[1]);
		}
		return array(
				'start' => $this->requestStartTime,
				'end' => $this->requestEndTime,
				'duration' => $this->requestEndTime - $this->requestStartTime,
				'duration_str' => $this->getDataFormatter()->formatDuration($this->requestEndTime - $this->requestStartTime),
				'counter' => count($this->data),
				'table' => $data
		);
	}
	
	public function getName()
	{
		return 'time';
	}
	
	public function getWidgets()
	{
		$widget = "PhpDebugBar.Widgets.HtmlVariableListWidget";
		if(count($this->data)){
			return array(
					"time" => array(
							"icon" => "clock-o",
							"tooltip" => "运行时间",
							"map" => "time.duration_str",
							"default" => "'0ms'"
					),
					"timeline" => array(
							"icon" => "tasks",
							"widget" => "PhpDebugBar.Widgets.HtmlVariableListWidget",
							"map" => "time.table",
							"default" => "{}"
					),
					"timeline:badge" => array(
							"map" => "time.counter",
							"default" => 0
					)
			);
		}else{
			return array(
					"time" => array(
							"icon" => "clock-o",
							"tooltip" => "运行时间",
							"map" => "time.duration_str",
							"default" => "'0ms'"
					)
			);
		}
	}
}
