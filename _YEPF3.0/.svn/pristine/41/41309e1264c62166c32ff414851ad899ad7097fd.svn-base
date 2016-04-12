<?php
namespace mdbao;
use mdbao\Topic as Topic;
use mdbao\User as User;

//定义model类
class Url extends BaseMongoRecord{
	protected static $schema = 
					array( 
		                _id=>'objectid',
						title				=>	'string',		//标题
						owner_id			=>	'string',		//所有者id
						topic_id			=>	'string',		//主题id
						from_url_id			=>	'string',		//从哪里转发的URL_ID
						'from'				=>	'string',		//转自url的创建者
						description			=>	'string',		//导读，摘要，说明
						url					=>	'string',		//来源url地址
						url_md5				=>	'string',		//url_md5
						image_md5			=>	'string',		//图片md5
						img_url				=>	'string',		//图片地址
						thumb_url			=>	'string',		//图片缩略处理地址
						media_url			=>	'string',		//音视频原文件地址
						'type'				=>	'integer',		//url的类型 enum URLType{ WWW=0, //默认-网页 NEWS=1, //资讯新闻 PIC=2, //图片 VIDEO=3,//视频 AUDIO=4,//音频 ECOM=5,//电商 EXP=6 //心得 SER=7, //组图//废弃 SET=8, //搭配//废弃 TXT=9  //纯文字}
						display_width		=>	'integer',		//封面原图宽度
						display_height		=>	'integer',		//封面原图高度
						create_date			=>	'datetime',		//创建转发的日期
						likes				=>	'integer',		//喜欢的数量
						reposts				=>	'integer',		//站内转发的次数
						collects			=>	'integer',		//被杂志收录的次数
						comments			=>	'integer',		//评论的次数
						passitive			=>	'integer',		//正面的digg数
						negative			=>	'integer',		//负面的digg数
						action				=>	'integer',		//自己贴的还是转发的
						nice_repost_path	=>	'string',		//转发自xx的xx
						visibility			=>	'integer',		//删除状态
						host				=>	'string',		//来源网站host
						level				=>  'integer',		//级别 0:普通，5:中级 (频道页)10:高级（内容进首页，频道页）
						client				=>	'integer',		//客户端名称
						stick				=>	'integer',		//置顶标识
						org_url_id			=>	'string',		//原始URL的ID
						last_update			=>	'integer',		//最后修改时间
						't_qq_id'           =>  'string',       //qq微博 id
						'plugin'            =>  'array',        //plugin信息
						'tag'				=>	'string',		//标签信息（用空格区分）
						'comment'			=>	'array',		//最近的50条评论数据，内容完全与comment表相同
						'catalog0'			=>	'string',		//一级分类
						'catalog1'			=>	'string',		//二级分类
						'catalog2'			=>	'string',		//三级分类
						'stamp'				=>	'array',		//荣誉印章
						'app_visibility'	=>	'integer',		//APP审核标记：注意默认为0
					    'contributor'	    =>	'string',		//投稿人ID
					    'audit'				 =>	'integer'		//审核标记  0为未审核，默认值；1审核过（现在只用于360）
							
					);
	//jugglin 自动转义
	//essential 必填
	//default 默认值
	//min 最小值
	//max 最大值
	protected static $schema_ext = 
					array(
										title			=>	array(),
										owner_id		=>	array(	'jugglin'	=>	1	),
										topic_id		=>	array(	'jugglin'	=>	1	),
										from_url_id		=>	array(	'jugglin'	=>	1	),
										'from'			=>	array(	'jugglin'	=>	1	),
										description		=>	array(),
										url				=>	array( 'default'	=>	''),
										url_md5			=>	array('jugglin'	=>	1),
										image_md5		=>	array('jugglin'	=>	1),
										img_url			=>	array(),
										thumb_url		=>  array( 'default' =>'',	'jugglin'	=>	1 ),
										media_url		=>	array(	'default'	=>	''),
										type			=>	array(	'default'	=>	0,	'jugglin'	=>	1	),
										display_width	=>	array(	'default'	=>	465	,	'jugglin'	=>	1),
										display_height	=>	array(	'default'	=>	473	,	'jugglin'	=>	1),
										likes			=>	array(	'default'	=>	0	),
										reposts			=>	array(	'default'	=>	0	),
										collects		=>	array(	'default'	=>	0	),
										comments		=>	array(	'default'	=>	0	),
										passitive		=>	array(	'default'	=>	0	),
										negative		=>	array(	'default'	=>	0	),
										action			=>	array(),
										nice_repost_path=>	array(),
										visibility		=>	array(	'default'	=>	0	,	'jugglin'	=>	1),
										level		    =>	array(	'default'	=>	0	),
										host			=>	array(),
										client			=>	array(),
										stick			=>	array(	'default'	=>	0,	'jugglin'	=>	1	),
										org_url_id		=>	array(	'jugglin'	=>	1,	'default'	=>	''	),
										't_qq_id'       =>  array(	'jugglin'	=>	1,	'default'	=>	''	),
							            'plugin'        =>  array(	'jugglin'	=>	1,	'default'	=>	array()	),
										'comment'        =>  array(	'jugglin'	=>	0,	'default'	=>	array()	),
										'catalog0'		=>	array(	'default'	=>	''	),
										'catalog1'		=>	array(	'default'	=>	''	),
										'catalog2'		=>	array(	'default'	=>	''	),
										'app_visibility'=>	array(	'default'	=>	0),
										'audit'			=>	array(	'default'	=>	0),
							            'contributor'	=>	array(	'default'	=>	''),
					);

	//check topic_id
	public function validatesTopicId(){
		$topicOne	=	Topic::findOne(	array(	'_id'	=>	new \MongoId($this->topic_id) ),
										array(),
										array()
										);
		if($topicOne){
			return true;
		}else{
			throw new \Exception('主题id无效，不存在此主题！:topic_id');
			return false;
		}
	}
	
	//check owner_id
	public function validatesOwnerId(){
		$ownerOne	=	User::findOne(	array(	'_id'	=>	strval($this->owner_id) ),
										array(),
										array()
										);
		if($ownerOne){
			return true;
		}else{
			throw new \Exception('用户id无效，不存在此用户！:owner_id');
			return false;
		}
	}
	
	/**
	 * 是否存在url
	 * @param mongoid $id
	 * @throws \mdbao\yoka_Error
	 */
	public static function isExists($url_id){
		if(self::findOne(array( '_id'	=>	new \MongoId($url_id) ),array('_id'=>1))){
			return true;
		}else{
			throw new \Exception(__CLASS__.'->'.__METHOD__.' Url 表中不存在 $id 内容!');
			return false;
		}
	}
	
	
}