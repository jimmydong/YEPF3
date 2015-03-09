<?php
/**
 * 
 * @author liangxuezhi
 *
 */
namespace yoka;

class YokaService 
{  
    
	//cpu信息。目录信息
    public $cpu_list = "";
    public $tree = "";


    
    /**
	 * 获取服务器时间
	 * @name    getServiceTime
	 * @return  返回服务器时间
     */
    static function getServiceTime($format="Y-m-d H:i:s")
    {
		return date($format);
    }
    
    
  	/**
	 * 获取服务器信息
	 * @name    getServiceData
	 * @return  返回服务器的负载信息
	 * $length  显示进程的长度
	 * $method	cpu_list  汇总信息  process_list  详细进程信息
	 */
	static function getServiceData($method='composite_list',$length=10)
	{
		//综合信息
		$composite_list=array();
		//进程信息
		$process_list=array();

		exec ("top -n 1 -b | head -n $length",$lasr_lint,$status);
		if($method=='composite_list')
		{
			for($i=0;$i<=5;$i++)
			{
				if(!empty($lasr_lint[$i]))
				{
					$composite_list[$i]=explode(',',$lasr_lint[$i]);
				}

			}
			return $composite_list;
		}
		else if($method=='process_list')
		{
			for($j=5;$j<=$length;$j++)
			{
				if(!empty($lasr_lint[$j]))
				{
					$process_list[$j]=explode(' ',$lasr_lint[$j]);
				}
			}
			return $process_list;
		}
		
	}  
    /**
     * 获取服务器磁盘空间
     * 
     */ 
	static function getServiceDiskSpace()
    {
		//硬盘信息
		$hard_disk_list=array();
		exec('df -hl',$lasr_lint,$status);
        
		foreach($lasr_lint as $key=>$lint)
		{
			if($key!=0)
			{
				//获取信息
				$hard_disk_list[substr($lint,strpos($lint,'%')-3,3)]=$lint;
			}

		}
		
		return $hard_disk_list;
    }
    
    
     /**
      *获取服务器的目录 
      */
     static	function getServiceFileList($path,$no_dir_string="",$type="")
     {
		
        $file_list=array();
        $file_data_list=array();
        //获取文件目录
        $file_list=self::getServiceFile($path,$no_dir_string,$type);
    
       	if(!empty($file_list))
       	{  
	        //将多维数组转换为一维
	        $file_list=self::rebuild_array($file_list);
	       
	        foreach($file_list as $key=>$file)
	        {
	            //获取文件的详细信息
	            $file_data_list[$file]=self::getServiceFileData($file);
	        }
	        unset($file_list);
       	}
        return  $file_data_list;
     }
     

    /**
     * 获取mysql	    	信息
     * $mysql_server   mysql链接 配置名称
     * $table          表名
     */
	static	function getServiceMysql($mysql_server='default',$table="")
	{
		//服务开始时间
        $time_start = microtime();
        //mysql信息
        $server_mysql=array();
        //是否成功
        $success=1;
        //服务结束时间
        $time_end="";
        
        $mdb = \yoka\DB::getInstance($mysql_server);
        if(!empty($table) && !empty($mdb))
        {
            $sql='select * from '.$table.' LIMIT 1';
            $data=$mdb->query($sql);
            
            //服务结束时间
            $time_end = microtime();
            
            if(empty($data))
            {
                $success=0;
            }
        }
        else
        {
            $success=0;
        }
        //计算总共使用时间
        $time = $time_end - $time_start;
        //是否正常 true正常 false不正常
        $server_mysql['success']=$success;
        //运行时间
        $server_mysql['time']=round($time,5); 
        return $server_mysql;
	}

	 /**
     * 获取memcached    信息
     * $cache_type      保存类型
     * $cache_server    服务的链接
     */
	static	function getServiceMemcached($cache_server='default',$cache_type="")
	{
		//服务开始时间
        $time_start = microtime();
        //mencached 信息
        $server_memcached=array();
        $mem = \Cache::getInstance($cache_server);
      
        if(strtolower($cache_type)=='commcache')
        {
            //获取对象
            $mem = \CommCache::getInstance($cache_server);
        }  
        $mem->set('yoka_test','yoka');
        $data=$mem->get('yoka_test');
        //是否成功
        $success=1;
        if($data!='yoka')
        {
            $success=0;
        }
        //服务结束时间
        $time_end = microtime();
        //计算总共使用时间
        $time = $time_end - $time_start;
        //是否正常 true正常 false不正常
        $server_memcached['success']=$success;
        //运行时间
        $server_memcached['time']=round($time,5);
        
        return $server_memcached;
	}
    
    
    /**
     * 返回数据配置信息
     * $_GET            get数据
	 * $data_list			配置信息数组
     */
    static  function getServiceInfo($_GET,$data_list)
    {
     
		$file = $data_list['file'];							//读取目录
		$no_dir_string = $data_list['no_dir_string'];		//不读的目录
		$mysql_server = $data_list['mysql_server'];			// mysql链接 变量名称
		$table = $data_list['table'];							// 表名
		$cache_server = $data_list['cache_server'];			// Memcached服务链接 变量名称
		$cache_type = $data_list['cache_type'];				//Memcached 类型
		$sphinx_success = $data_list['sphinx_success'];		//sphinx服务是否正常运行 true false
		$log_file = $data_list['log_file'];					//日志目录 绝对地址
		$memcached_success = $data_list['memcached_success'];  //Memcached 服务是否正常运行 1 正常  0为不正常  2为没有服务
		$sphinx_success = $data_list['sphinx_success'];		//sphinx服务是否正常运行 1 正常  0为不正常  2为没有服务
		//释放
		unset($data_list);
		
		//获取服务器时间、负载信息、磁盘空间
        $server_info=array();
        
        //获取服务器时间
        $server_info['time']= self::getServiceTime();

        //获取负载信息
        $server_info['load']= self::getServiceLoad();

        //获取服务器磁盘空间
        $server_info['disk']= self::getServiceDiskSpace();
 
        $type=$_GET['type'];
      
        if(!empty($type))
        {
            $file_type=".".$type;
            //获取服务器的目录   目录要写绝对地址
            $server_info['file_php']=self::getServiceFileList($file,$no_dir_string,$file_type); 
        }

       
        if(!empty($table))
        {
            //获取服务器mysql服务是否正常
            $server_info['mysql']=self::getServiceMysql($mysql_server,$table);                 
        }
        if($memcached_success==2)
        {
             //获取服务器memcached服务是否正常 
            $server_info['memcached']['success']=$memcached_success;
        }
        else
        {
            //获取服务器memcached服务是否正常 
            $server_info['memcached']=self::getServiceMemcached($cache_server,$cache_type);
        }
      
        //获取服务器sphinx服务是否正常 
        $server_info['sphinx']['success']=$sphinx_success;
    
        return  $server_info;
    }
    
    /**
     * 返回验证key
     * 
     */
    static function getYokaKey()
    {
        $yoka_key="yoka.com";
    	return md5($yoka_key);
    }



	/**
	 * 获取服务器负载信息
	 * @name    getServiceLoad
	 * @return  返回服务器的负载
	 */
	static function getServiceLoad()
	{
		//整理后的cpu信息
		$loade_list=array();	
		
		if(empty($cpu_list))
		{
			//获取服务器信息
			$cpu_list=self::getServiceData('composite_list');
		}
        if(!empty($cpu_list[0]))
        {
            
    		//循环取出各项cpu百分比
    		foreach($cpu_list[0] as $key=> $cpudate)
    		{
    
    			if($key==3)
    			{
    				$loade_list['1minutes']=substr($cpudate,strpos($cpudate,'load average:')+13);
    			}else if($key==4)
    			{
    				$loade_list['5minutes']=$cpudate;
    			}else if($key==5)
    			{
    				$loade_list['15minutes']=$cpudate;
    			}
    				
    			
    		}
       }

		$loade_list_info=array();
		$loade_list_info['loade']=$loade_list;
		$loade_list_info['cpu'] = self::getServiceCpuRate();
		$loade_list_info['memory'] = self::getServiceMemoryData();
		$loade_list_info['memcache']=self::getServiceProcess('memcache',20);
		return $loade_list_info;
	}

	/**
	 * 获取服务器cpu详细信息
	 * @name    getServiceCpuRate
	 * @return  返回服务器的cpu信息
	 */
	static function getServiceCpuRate()
	{
		//整理后的cpu信息
		$cpu_rate_list=array();	

		if(empty($cpu_list))
		{
			//获取服务器信息
			$cpu_list=self::getServiceData('composite_list');
		}
        if(!empty($cpu_list[2]))
        {
    		//循环取出各项cpu百分比
    		foreach($cpu_list[2] as $key=> $cpudate)
    		{
    
    			if($key==0)
    			{
    				$cpu_rate_list['us']=substr($cpudate,strpos($cpudate,'Cpu(s):')+8,strpos($cpudate,'%')-(strpos($cpudate,'Cpu(s): ')+8));
    			}else if($key==1)
    			{
    				$cpu_rate_list['sy']=substr($cpudate,0,strpos($cpudate,'sy')-1);
    			}else if($key==3)
    			{
    				$cpu_rate_list['id']=substr($cpudate,0,strpos($cpudate,'id')-1);
    			}else if($key==4)
                {
                    $cpu_rate_list['wa']=substr($cpudate,0,strpos($cpudate,'wa')-1);
                }
    				
    			
    		}
      }

		return $cpu_rate_list;		
	}

	/**
	 * 获取服务器内存信息
	 * @name    getServiceMemoryData
	 * @return  返回服务器的内存信息
	 */
	 static function getServiceMemoryData()
	 {
		//整理后的内存信息
		$new_memory_list=array();	

		if(empty($cpu_list))
		{
			//获取服务器信息
			$cpu_list=self::getServiceData('composite_list');
		}
        if(!empty($cpu_list[3]))
        {
            
    		//循环取出各项使用内存
    		foreach($cpu_list[3] as $key=> $memorydate)
    		{
    
    			if($key==0)
    			{
    				$new_memory_list['total']=trim(substr($memorydate,strpos($memorydate,'Mem: ')+5,strpos($memorydate,'k')-(strpos($memorydate,'Mem: ')+5)));
    			}else if($key==1)
    			{
    				$new_memory_list['used']=trim(substr($memorydate,0,strpos($memorydate,'k')));
    			}else if($key==2)
    			{
    				$new_memory_list['free']=trim(substr($memorydate,0,strpos($memorydate,'k')));
    			}
    				
    		}
        }

		return $new_memory_list;
	 }

	/**
	 * 获取进程信息
	 * @name   getServiceProcess
	 * $processname  进程名称
	 * $length		执行命令时显示进程数量
	 * @return 返回进程信息空
	 */
	 static  function getServiceProcess($processname,$length=10)
	 {
		//进程信息
		$new_process_list=array();	

		if(empty($process_list))
		{
			//获取服务器信息
			$process_list=self::getServiceData('process_list',$length);
		}
		//循环取出各项使用内存
		foreach($process_list as $key=> $process)
		{
			//去除空值
			self::array_remove_empty($process);
			if(empty($processname))
			{
				$new_process_list[$key]=$process;
			}//判断进程命令
			elseif($process[2]==$processname)
			{
				$new_process_list[0]=$process_list[6];
				$new_process_list[$key]=$process;
			
			}
				
		}

		return $new_process_list;

	}


	/**
	 * 去除数组中空值
	 * @name   array_remove_empty
	 */	
	 static function array_remove_empty(&$arr, $trim = true)
	 {

		foreach ($arr as $key => $value) {
			if (is_array($value)) {
				self::array_remove_empty($arr[$key]);
			} else {
				$value = trim($value);
				if ($value == '') {
					unset($arr[$key]);
				} elseif ($trim) {
					$arr[$key] = $value;
				}
			}
		}
	}



	/**
	 * 获取文件信息
	 * @name   getServiceFileData
	 */	
	static function getServiceFileData($file_path)
	{
		//文件信息
		static $file_data=array();
		//文件名称
		$file_data['name']=$file_path;
		//文件大小
		$file_data['size']=filesize($file_path);
		//修改时间
		$file_data['time']=date("Y-m-d H:i:s",filemtime($file_path));
		
		return $file_data;
		
	}


	
	/**
	 * 返回目录文件
	 * getServiceFile  
	 * $path   路径
	 * $no_dir_string  不返回的文件夹名称  如：'upload,_TEMPLATE_C'
	 * $type	要文件的文件类型  默认为.php
	 * @return 返回文件目录和名称
	 */	
	static function getServiceFile($path,$no_dir_string="",$type="")
	{
		$no_dir_list=explode(",",$no_dir_string);
		foreach($no_dir_list as $no_dir)
		{
			if(!empty($no_dir) && strpos($path,$no_dir)!=0)
			{
				
				return "";
			}
		}
		foreach (glob($path.DIRECTORY_SEPARATOR.'*') as $key=>$f) 
		{
			//如果是文件夹继续读取
			if(is_dir($f))
			{
				$tree[$f]=self::getServiceFile($f,$no_dir_string,$type);
			}//判断文件类型是否为空，非空执行
			else if(!empty($type))
			{
				//判断是否为$type类型文件
				if(strpos($f,$type)!=0)
				{
					$tree[$f]=$f;
				}

			}else
			{
				$tree[$f]=$f;
			}
		}
		unset($no_dir_list);
		return $tree;
	}


	/**
	 * 将多维转换在一维
	 * rebuild_array  
	 * $arr   要转换的数组
	 * @return 返回一维数组
	 */	
	static function rebuild_array($arr)
	{ 
		static $tmp=array();

		foreach($arr as $key=>$val)
		{
			//判断是否为数组，不是执行
			if(is_array($val))
			{
				self::rebuild_array($val);
			}
			else if(!empty($val) )
			{
				$tmp[] = $val;
			}
		}
		
		return $tmp;
	}
    


    /**
     * 返回指定格式服务器数据
     * $_GET Get    请求数据
     * $log_file    日志目录绝对地址
     * $data        返回服务器数据信息
     */
    static function getServiceTypeData($_GET,$data,$log_file)
    {
        
   	    $yokaservice_utility = new YokaServiceUtility();
        $params = $yokaservice_utility->processRequest();
      	$model = $_GET['model'];	
        $app_sign = $_GET['sign'];			// 来源应用的校验码
        $type = $_GET['type'];       //文件类型
        $format = $_GET['format'];
        $outdata = array();			 // 包括 error_id, error_msg, data 三项
        //app_key信息
        $app_key = 'yoka.com';
        $yokaservice_utility->secret = $app_key;
        $sign = $yokaservice_utility->createSign($params);
        
        $file_type="";
        
        /*if ($app_sign != $sign)
        {	 //校验失败
        	$outdata['error_msg'] = 'error_code=wrong_sign';
            Log::customLog($customLogFileName,"校验失败:".$outdata['error_msg']."\n");
        }
        else*/ if ($model=="data")
		{

			$outdata['data'] =$data;
		 }
		else
		{
			$outdata['error_msg'] = 'model='.$model;
			
		}
		
		// 格式化输出内容
		if ($format == 'json')
		{
			$str_output = $yokaservice_utility->createJson($outdata);
		}
		elseif ($format == 'xml')
		{
			$str_output = $yokaservice_utility->createXml($outdata);
		}
		else
		{
			$str_output = serialize($outdata);
		}
		//变量释放
		unset($_GET);
		unset($outdata);
        unset($cpu_list);
        unset($tree);
        
		return $str_output = $yokaservice_utility->output($str_output);

        
    }
	


}
?>