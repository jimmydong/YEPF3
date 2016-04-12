<?php
/**
 * 辅助工具： 转换YOKA-ENV.php 至 YOKA-ENV-3.0.php
 * 【注意】
 * 		仅转换确定规则部分，其他需要人工处理
 * 		转换完成后，请将全局部分提取合并，用 namespace {}包含
 * 		同一namespace多个段落的，建议合并至一处
 */
include('../../init.php');
$t = file('YOKA-ENV.php');
$counter = 0;
$namespace = '';
foreach($t as $line){
	if(preg_match('/\$_SERVER\[\'(MDB|SDB)_(.*)_(.*)\'\] = \'(.*)\';/', $line, $reg)){
		//Debug('reg', $reg);
		if($namespace != $reg[3]){
			$namespace = $reg[3];
		}
		if($namespace == 'DEFAULT' || $namespace == 'default')$namespace = 'COMMON'; //不能与缺省关键字相同
		$db[$counter-1][$namespace][] = "		{$reg[1]}_{$reg[2]} = '{$reg[4]}'";
	}elseif(preg_match('/\$_SERVER\[\'(CACHE)_(.*)_(.*)\'\] = \'(.*)\';/', $line, $reg)){
		//Debug('reg', $reg);
		if($namespace != $reg[2]){
			$namespace = $reg[2];
		}
		if($namespace == 'DEFAULT' || $namespace == 'default')$namespace = 'COMMON'; //不能与缺省关键字相同
		$db[$counter-1][$namespace][] = "		{$reg[1]}_{$reg[3]} = '{$reg[4]}'";
	}else{
		$re[$counter] = trim($line);
		$counter++;
	}
}
//Debug('re', $re);
//Debug('db', $db);
$text = ";;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;\n";
$text .= "; 由转换工具自动生成，注意需要手工修正   \n";
$test .= "; 1， 删除PHP语句 及 ;";
$test .= "; 2， 删除注释/**/ 及  //";
$test .= "; 3， 修改注释 # 为 ;";
$text .= "\n";
$text .= ";全局设定\n";
$text .= "[yoka]\n";
$text .= "	ENV_VERSION	= '3.0(".date('Y-m-d H:i:s').")'\n";
$text .= "	YEPF_PATH_3 = '/YOKA/HTML/_YEPF3.0'\n\n";
foreach($re as $counter=> $line){
	if($db[$counter])continue;
	if(preg_match('/<\?|\?>/',$line))continue;
	if(preg_match('/\$_SERVER\[\'(.*)\'\](.*);/', $line, $reg))$line = "	{$reg[1]}{$reg[2]}";
	$text.= $line . "\n";
}
$text .= ";全局设定结束\n\n";
foreach($re as $counter=> $line){
	if($db[$counter]){
		if($line)$text.= $line . "\n";
		foreach($db[$counter] as $namespace=> $block){
			$text.= "[{$namespace}] \n";
			foreach($block as $k=> $v){
				$text.= "$v \n";
			}
			$text.= "\n";
		}
	}
}
$text = str_replace("\n\n\n", "\n", $text);
$text = str_replace("\n\n\n", "\n", $text);
$text = str_replace("\n\n\n", "\n", $text);
echo "<pre>" . htmlspecialchars($text) . "</pre>";
//file_put_contents('YOKA-ENV.ini',$text);

