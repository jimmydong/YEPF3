<?php
/**
 * @name Rank.class.php
 * @desc 按小时权重计算
 * @author caoxd
 * @createtime 2009-02-16 12:04
 * @updatetime
 * @usage
 *
 * @todo
 */
namespace ext;

if(!defined('YOKA')) exit('Illegal Request');

class Rank
{
	private $rankBase = array();
	private $rank = array() ;
	public function __construct()
	{
		$rank = array();
		$rank[0] = 0.015722417 ;
		$rank[1] = 0.009702611 ;
		$rank[2] = 0.006195278 ;
		$rank[3] = 0.004563095 ;
		$rank[4] = 0.003916706 ;
		$rank[5] = 0.00375543 ;
		$rank[6] = 0.003933375 ;
		$rank[7] = 0.007733405 ;
		$rank[8] = 0.052883734 ;
		$rank[9] = 0.084674841 ;
		$rank[10] = 0.076712754 ;
		$rank[11] = 0.073737241 ;
		$rank[12] = 0.068024144 ;
		$rank[13] = 0.073797319 ;
		$rank[14] = 0.070695055 ;
		$rank[15] = 0.074647479 ;
		$rank[16] = 0.076346333 ;
		$rank[17] = 0.055603184 ;
		$rank[18] = 0.037834632 ;
		$rank[19] = 0.041062272 ;
		$rank[20] = 0.044357134 ;
		$rank[21] = 0.043447354 ;
		$rank[22] = 0.039781308 ;
		$rank[23] = 0.030872901 ;
		$this->rankBase = $rank;
	}

	/**
	 * 根据给定的开始与结束时段参数，动态设计$this->rank
	 *
	 * @param int $intHourStart
	 * @param int $intHourEnd
	 */
	private function setRank($intHourStart, $intHourEnd) {
	    if($intHourStart>0 || $intHourEnd<23) {
		    $arrRank = array_slice($this->rankBase, $intHourStart, ($intHourEnd-$intHourStart+1), true);
	        $intSum = array_sum($arrRank);
	        $this->rank = array_fill(0, 24, 0);
	        foreach($arrRank as $key => $val) {
	            $this->rank[$key] = $val/$intSum;
	        }
        } else {
        	$this->rank = $this->rankBase;
        }
	}

	/**
	 * @name getTimeValue
	 * @desc 获得每个时间段应该获得的奖品
	 * @param int $num
	 * @param int $intHourStart 从该小时开始。$intHourStart >=0 && $intHourStart <= 23
	 * @param int $intHourEnd 到该小时结果。$intHourEnd <= 23 && $intHourEnd >= $intHourStart
	 */
	public function getTimeValue($num, $intHourStart = 0, $intHourEnd = 23)
	{
		$this->setRank($intHourStart, $intHourEnd);
		$value = array();
		$value = array_fill(0, 24, 0);
		$dec = $num;
		for($intHour=$intHourStart;$intHour<=$intHourEnd;++$intHour) {
			$cur = floor($this->rank[$intHour] * $num) ;
            $dec -= $cur;
            $value[$intHour] = $cur;
		}
		while ($dec) {
			$value[rand($intHourStart, $intHourEnd)] ++ ;
			$dec-- ;
		}

		$total = 0 ;
	    for($intHour=$intHourStart;$intHour<=$intHourEnd;++$intHour) {
	    	$total += $value[$intHour] ;
	    	$value[$intHour] = $total;
        }
		return $value;
	}
	/**
	 * @name winnerCal
	 * @desc 计算是否胜出发奖品
	 */
	public function winnerCal($rank, $prized)
	{
		$hour = date('G');
		$should = $rank[$hour];

	    while($prized >= $should && $hour < 23) {
                $should += $rank[++$hour];
        }

	    if($should <= $prized) {//应发数小于等于实发数，则不胜出
            return false;
        }

        $now = $hour == date('G') ? time() : mktime($hour, 0, 0);

		$random = array();
		$max = mktime($hour, 0, 0) + 60 * 60;

		$intWillSend = $should - $prized;//应发-实发=将发

		# 获取循环次数
		$intSecondBetween = $max - $now + 1;//用于命中奖品的总秒数
		if($intWillSend/$intSecondBetween > 10) {//将发是用于命中奖品的总秒数的十倍以上时，直接返回当前秒
			return $now;
		}

		$intLoopTimes = $intWillSend;
		while ($intLoopTimes) {
			$random[] = rand($now, $max);
			$intLoopTimes -- ;
		}
		$min = self::getMinNum($random);
		return $min;
	}
	/**
	 * @name getMinNum
	 * @desc 获得数组中最小的数
	 */
	public function getMinNum($array)
	{
		$min = 0 ;
		foreach ($array as $v)
		{
			if($min == 0 || $min > $v)
			{
				$min = $v;
			}
		}
		return $min;
	}
}
?>