<?php
namespace yoka;
/**
 * 反Ddos攻击
 * 
 * 使用示例参见 filter 方法。
 */
class AntiDdos{
	/**
	 * 过滤
	 * @param \yoka\Cache $cache  		快速缓冲实例
	 * @param string $project		项目名称
	 * @param string $key			本次请求的关键内容
	 * @param int $minute			防攻击间隔（秒）
	 * @param int $limit			每个间隔中允许的次数
	 * @param int $recover			屏蔽后恢复时间（秒）
	 * 
	 * 使用： 
	   	$cache = \yoka\Cache::getInstance('aibangmang');
    	$re = \yoka\AntiDdos::filter($cache, 'sendSms', 'test001', 60, 30, 7200);
    	60秒内超过内30次，则封禁7200秒钟
	 */
	public static function filter($cache, $project, $key='', $second=60, $limit=30, $recover=7200){
		if(! is_callable(array($cache,'get'))){
			//传入实例不可用。全部返回正确
			return true;
		}
		if(! is_string($key)) $key = json_encode($key);
		$keyMd5 = md5($key);
		//检查是否已经封禁
		$keySeal = 'AntiDdos_Seal_'.$project.'_'.$keyMd5;
		if( $seal = $cache->get($keySeal) ){
			if($seal + $recover > time()){
				//仍在封禁期
				return false;
			}
		}
		//按间隔数累加
		$cell = intval(time() / $second);
		$keyCheck = 'AntiDdos_Check_'.$project.'_'.$keyMd5.'_'.$cell;
		$counter = $cache->increment($keyCheck);
		if($counter > $limit){
			//加入封禁
			if($cache->get($keySeal)){
				$cache->set($keySeal, time()+$recover); //以前就封禁过的，加倍封禁！
			}else{
				$cache->set($keySeal, time());
			}
			return false;
		}
		
		//允许访问
		return true;
	}
}