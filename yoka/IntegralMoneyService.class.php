<?php
/**
 * 执行积分金币操作的封装辅助类
 *
 */
class IntegralMoneyService
{
	
	private $service_url = 'http://vgrade.yoka.com/service/service.php';
	

	/**
     * 构造函数.
     */
	public function  __construct($secret='') 
	{
		$this->yokaservice_utility = new YokaServiceUtility();
		//$this->yokaservice_utility->secret = $secret; // $secret= md5('YOKA.COM.Service'.(string)date('l-F'));
		$this->yokaservice_utility->secret = md5('YOKA.COM.Service'.(string)date('l-F'));
	}



	/**
	 * 用户执行积分操作
	 * @param array $params	mix数据
	 * @param  int $parameters['userid']  用户ID必填
	 * @param  string $parameters['username']  用户名称必填
	 * @param  int $parameters['groupid']  身份体系ID必填
	 * @param  int $parameters['optionid']  操作项ID必填
	 * @param  int $parameters['type']   积分加减类型选填项. 如果没有则按照optionid设定的读取.
	 * @param  int $parameters['value']  积分数量选填项,. 如果没有则按照optionid设定的读取. 有则按照参数设定.
	 * @param  int $parameters['log']  日志选填项,如果没有则取操作项的名称. 有则按参数设定.
	 * @return mix 执行的结果
	 */
	public function integral($params)
	{
		
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='integral';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params);
		
	}
	
	/**
	 * 用户执行货币操作
	 * @param array $params	mix数据
	 * @param  int $parameters['userid']  用户ID必填
	 * @param  string $parameters['username']  用户名称必填
	 * @param  int $parameters['groupid']  身份体系ID必填
	 * @param  int $parameters['optionid']  操作项ID必填
	 * @param  int $parameters['type']   积分加减类型选填项. 如果没有则按照optionid设定的读取.
	 * @param  int $parameters['value']  积分数量选填项,. 如果没有则按照optionid设定的读取. 有则按照参数设定.
	 * @param  int $parameters['log']  日志选填项,如果没有则取操作项的名称. 有则按参数设定.
	 * @return mix 执行的结果
	 */
	public function money($params)
	{
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='money';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params);
	}
	
	
	/**
	 * 获取用户的等级和积分
	 * @param  int $parameters['userid']  用户ID必填
	 * @param  int $parameters[groupid] 身份体系ID必填
	 * @return max 任何数据类型
	 */
	public function getIntegralInfo($params)
	{
		
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='getIntegralInfo';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params);
	}
	
	
	/**
	 * 获取用户的等级和货币
	 * @param  int $parameters[userid]  用户ID必填
	 * @param  int $parameters[groupid] 货币体系ID必填
	 * @return max 任何数据类型
	 */
	public function getMoneyInfo($params)
	{
		
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='getMoneyInfo';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params);
	}
	
	
	/**
	 * 获取用户的身份体系和货币体系数据
	 * @param  int $parameters[userid]  用户ID必填
	 * @return max 任何数据类型
	 */
	public function getIntegralMoneyInfo($params)
	{
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='getIntegralMoneyInfo';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params,'get');
		
		
	}
	
	
	/**
	 * 批量获取用户信息
	 * @param  string $params['userid']  用户字符 8,3389374,34576
	 * @param  int $params['groupid']    身份体系组ID
	 * @return max 任何数据类型
	 */
	public function getMoreUserMoneyInfo($params)
	{
		
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='getMoreUserMoneyInfo';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params);
	}
	
	
	/**
	 * 批量获取用户信息
	 * @param  string $params['userid']  用户字符 8,3389374,34576
	 * @param  int $params['groupid']    身份体系组ID
	 * @return max 任何数据类型
	 */
	public function getMoreUserIntegralInfo($params)
	{
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='getMoreUserIntegralInfo';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params,'get');
		
		
	}
	
	/**
	 * 根据积分获取等级
	 * @param  int $parameters[groupid]  组ID
	 * @param  int $parameters[value]   积分值
	 * @return max 任何数据类型
	 */
	 
	public function getIntegralLevelByValue($params)
	{
		
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='getIntegralLevelByValue';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params,'get');
	}
	
	
	/**
	 * 根据财富获取等级
	 * @param  int $parameters[groupid] 组ID必填
	 * @param  int $parameters[value]   财富值
	 * @return max 任何数据类型
	 */
	 
	public function getMoneyLevelByValue($params)
	{
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='getMoneyLevelByValue';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params,'get');
	}
	
	
	/**
	 * 根据获取等级
	 * @param  int $parameters[size] 数量
	 * @param  int $parameters[start]   开始点
	 * @param  int $parameters[where]   条件
	 * @param  int $parameters[order]   排序
	 * @param  int $parameters[showfield]   显示字段
	 * @return max 任何数据类型
	 */
	 
	public function getIntegralLevelList($params)
	{
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='getIntegralLevelList';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params,'get');
	}
	
	
	/**
	 * 根据获取等级
	 * @param  int $parameters[size] 数量
	 * @param  int $parameters[start]   开始点
	 * @param  int $parameters[where]   条件
	 * @param  int $parameters[order]   排序
	 * @param  int $parameters[showfield]   显示字段
	 * @return max 任何数据类型
	 */
	 
	public function getMoneyLevelList($params)
	{
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='getMoneyLevelList';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params,'get');
	}
	
	
	
	/**
	 * 根据获取数据。少用，除非复杂逻辑
	 * @param  int $parameters[sql] SQL
	 * @return max 任何数据类型
	 */
	 
	public function getDataList($params)
	{
		//用户可以不传递以下两参数
		if(empty($params['method']))
		{
			$params['method']='getDataList';
		}
		if(empty($params['format']))
		{
			$params['format']='php';
		}
		
		$params['sign']=$this->yokaservice_utility->createSign($params);
		return $this->yokaservice_utility->request($this->service_url, $params,'get');
	}
	
}
?>