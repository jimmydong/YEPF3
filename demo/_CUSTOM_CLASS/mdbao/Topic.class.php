<?php
namespace mdbao;
use mdbao\User as User;
//定义model类
class Topic extends BaseMongoRecord{
	protected static $schema = array( 
										_id=>'objectid',
										title => 'string',				//主题名称
										owner_id =>'string',			//所有者id
										cover => 'string',				//头像地址
										type => 'integer',					//主题类型
										create_date =>'datetime',		//主题创建时间
										subscribers =>'integer',			//主题订阅数量
										urls => 'integer',						//url数量
										visibility =>'integer',				//删除状态
										related_topic_Id =>'string',			//相关主题ID
										description =>'string',				//描述
										viewed =>'integer',					//首页被访问的次数
										passitive =>'integer',				//正面的digg
										negative => 'integer',				//负面的digg
										votes =>'string',					//投票的人数
										background =>'string',				//背景
										icon =>'string',					//封面
										music =>'string',					//音乐
										last_update =>'datetime',			//最后更新时间
										create_voters =>'string',			//投票的人
										extra_html =>'string',				//额外的html
										theme =>'integer',					//样式
			                            catalog0 =>'string',				//顶级分类
			                            catalog1 =>'string',				//一级分类
			                            catalog2 =>'string',				//二级分类
										level =>'integer',					//级别 0:普通，1:高级（内容进首页）
										short_url =>'string',				//短链地址
										extra_right =>'string', //右侧导航区块
										extra_bottom =>'string', //底部宣传区块
										extra_marq =>'string',	//顶部轮转图
										extra_code =>'string', 	//销售添加检测代码用
										extra_option =>'string', //广告相关开关值
			                            'tag' => 'string',		//标签信息（用空格分隔）
			                            'app' => 'array',		//应用信息（内嵌数组）
										view_total => 'integer',
										view_data => 'array',
										view_yesterday => 'integer',
										domain_name => 'string',
										'group_id' => 'string',
										'face_image' => 'string',
                                        'face_mode'=>'integer', //顶部样式
                                        'face_html'=>'string', //额外代码
                                        'extra_ad'=>'string', 
                                        'optag'=>'array', //运营标签
			                            'audit'=>'array',

	);
	//jugglin 自动转义
	//essential 必填
	//default 默认值
	//min 最小值
	//max 最大值
	protected static $schema_ext = array(
										title		=>	array(),
										owner_id	=>	array(),
										cover		=>	array(),
										type		=>	array(),
										passitive	=>	array(	'default'	=>	0	),
										negative	=>	array(	'default'	=>	0	),
										subscribers	=>	array(	'default'	=>	0	),
										urls		=>	array(	'default'	=>	0	),
										visibility	=>	array(),
										votes =>array(	'default'	=>''),
										background =>array(	'default'	=>''),
										icon =>array(	'default'	=>''),
										music =>array(	'default'	=>''),
										create_voters =>array(	'default'	=>''),
										extra_html =>array(	'default'	=>''),
										theme =>array(	'default'	=>0),
			                            catalog0 =>array('default'	=>null),
			                            catalog1 =>array('default'	=>''),
			                            catalog2 =>array('default'	=>null),
										level =>array(	'default'	=>0),
										short_url =>array(	'default'	=>''),
										extra_right =>array(	'default'	=>''),
										extra_bottom =>array(	'default'	=>''),
										extra_marq =>array(	'default'	=>''),
										extra_code =>array(	'default'	=>''),
										extra_option =>array(	'default'	=>''),
			                            'tag' =>array(	'default'	=>''),
			                            'app' =>array(	'jugglin'	=>	0,	'default'	=>	array()	),
										'view_total' => array( 'jugglin' => 1, 'default' => 0),
										'view_yesterday' => array( 'jugglin' => 1, 'default' => 0),
										'group_id' => array('default'	=>''),
										'face_image' => array('default'	=>''),
			                            'face_mode' => array('jugglin' => 1, 'default' => 0),
			                            'face_html' => array( 'default' => ''),
		                                'extra_ad' => array( 'default' => ''),
                                        'optag'        =>  array(	'jugglin'	=>	1,	'default'	=>	array()	),
										);
	
	//model类验证方法
	public function validatesTitle(){
		//长度验证
		if ( $this->title == '' || mb_strwidth( $this->title) > 50 * 3 ){
			throw new \Exception("主题名称长度不正确！".mb_strwidth( $this->title));
			return false;
		}
		return true;
	}
	//验证owner_id存在
	 
	public function validatesOwnerId(){
		/*
		$ownerOne	=	User::findOne(	array(	'_id'	=>	$this->owner_id ),
										array(),
										array()
										);
		if($ownerOne){
			return true;
		}else{
			throw new \Exception('用户id无效，不存在此用户！:owner_id');
			return false;
		} */
		//@xwarrior should check user by front php code
		if ( !$this->owner_id || strlen($this->owner_id) == 0){
			throw new \Exception('用户id无效，不存在此用户！:owner_id');
		}else{
			return true;
		}
	} 
	
	/**
	 * 是否存在TOPIC
	 * @param mongoid $id
	 * @throws \mdbao\yoka_Error
	 */
	public static function isExists($topic_id){
		if(self::findOne(array( '_id'	=>	new \MongoId($topic_id) ),array('_id'=>1))){
			return true;
		}else{
			throw new \Exception( __CLASS__.'->'.__METHOD__.' Url 表中不存在 $id 内容!');
		}
	}
}