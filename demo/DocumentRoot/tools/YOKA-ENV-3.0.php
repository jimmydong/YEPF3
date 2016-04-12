;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;;
; 由转换工具自动生成，注意需要手工修正   
;全局设定
[yoka]
	ENV_VERSION	= '3.0(2014-10-08 15:04:33)'
	YEPF_PATH_3 = '/YOKA/HTML/_YEPF3.0'

/* YOKA-ENV.php FROM 41 */
	SERVER_ADDR)
#YEPF配置文件
	YEPF_PATH = '/YOKA/HTML/_YEPF'
	YEPF_IS_DEBUG = false
#COMMCACHE
	LOCAL_MEM_IP = "127.0.0.1"
	LOCAL_MAGENT_PORT = "11311"
	LOCAL_MEM_PORT = "11211"
	MAGENT_BACKUP_IP = "10.0.0.211"
	MAGENT_BACKUP_PORT = "11216"

#ATTACHMENTS
	SALE_ATTACHMENTS_PATH = '/var/www/data/photos_1/sale'
	SALE_ATTACHMENTS_DOMAIN = 'http://sap1.yokacdn.com'\n\n\n

//拜金城市场
	MDB_SERVER_USPACE_SHICHANG='10.0.10.21:6934'
	MDB_USER_USPACE_SHICHANG='uspace'
	MDB_PASS_USPACE_SHICHANG='uspace.yoka.com'
	MDB_DB_USPACE_SHICHANG='uspace_shichang'\n\n\n	MDB_SERVER_VGRADE='10.0.10.24:6933'
	MDB_USER_VGRADE='vgrade'
	MDB_PASS_VGRADE='fgQLgjSPe'
	MDB_DB_VGRADE='vgrade'\n\n\n

#LUCKY PRIVATE CONFIG
	ROOT_DOMAIN_LUCKY = 'http://lucky.yoka.com'
#ZHUANTI PRIVATE CONFIG
	ROOT_DOMAIN_ZHUANTI = 'http://space.yoka.com/zhuanti'

#vote.sec.yoka.com CONFIG
	ROOT_DOMAIN_VOTE = 'http://vote.sec.yoka.com'

#new space.yoka.com CONFIG
	ROOT_DOMAIN_BLOG = 'http://blog.yoka.com'
	ROOT_DOMAIN_BBS = 'http://bbs.yoka.com'
	ROOT_DOMAIN_BRAND = 'http://brand.yoka.com'
	ROOT_DOMAIN_BRANDSERVICE = 'http://brandservice.yoka.com'
	ROOT_DOMAIN_NEWSPACE = 'http://space.yoka.com'
	ROOT_DOMAIN_NEWPASSPORT = 'http://passport.yoka.com'
	ROOT_DOMAIN_NEWG = 'http://g.yoka.com'
	ROOT_DOMAIN_NEWTRY = 'http://try.yoka.com'
	ROOT_DOMAIN_NEWLUCKY = 'http://lucky.yoka.com'
	ROOT_DOMAIN_USPACE = 'http://uspace.yoka.com'
	ROOT_DOMAIN_UCENTER = 'http://ucenter.yoka.com'

	ROOT_DOMAIN_THUMB = 'http://thumb1.yokacdn.com'
	ROOT_DOMAIN_UCHOME_US1 = 'http://us1.yoka.com'
	ROOT_DOMAIN_SPACEPHOTO = 'http://sp1.yokacdn.com/photos'\n	ROOT_PATH_PUBAPI = '/var/www/uspace/api'
	PHOTO_PATH_SPACEPHOTO = '/storage/oldspace'
	WEBCP_DOMAIN = 'http://fashion02.yoka.com'\n#sphinx search
	SPHINX_USER_HOST = '10.0.0.231'
	SPHINX_USER_PORT = 3322
	SPHINX_USER_MAX_MATCHES = 1000\n

# secret config
	PAY_SECRET = 'g3Azt02f'

;全局设定结束

#MYSQL
[BIOTHERM] 
		MDB_USER = 'biotherm' 
		MDB_PASS = 'cwVpZxwQ06UWvXWuPq9X5' 
		MDB_SERVER = '10.0.0.22:6914' 
		MDB_DB = 'biotherm' 
		SDB_USER = 'biotherm' 
		SDB_PASS = 'cwVpZxwQ06UWvXWuPq9X5' 
		SDB_SERVER = '10.0.0.22:6914' 
		SDB_DB = 'biotherm' 

[LUCKY] 
		MDB_USER = 'lucky' 
		MDB_PASS = 'mNcTJTHIMUDB5#' 
		MDB_SERVER = '10.0.0.23:6917' 
		MDB_DB = 'lucky' 

[VOTE] 
		MDB_USER = 'public_vote' 
		MDB_PASS = '53cTJTFSB5#' 
		MDB_SERVER = '10.0.0.23:6917' 
		MDB_DB = 'public_vote' 

# 为新版空间博客、插件、试用新增加
[UCHOME] 
		MDB_USER = 'uspace' 
		MDB_PASS = 'uspace.yoka.com' 
		MDB_SERVER = '10.0.0.27:6924' 
		MDB_DB = 'uspace_uchome' 

[UCENTER] 
		MDB_USER = 'uspace' 
		MDB_PASS = 'uspace.yoka.com' 
		MDB_SERVER = '10.0.10.25:6935' 
		MDB_DB = 'uspace_ucenter' 

[UCHOME] 
		SDB_USER = 'uspace_slave' 
		SDB_PASS = 'fdLeG3g0' 
		SDB_SERVER = '10.0.0.33:6924' 
		SDB_DB = 'uspace_uchome' 

[UCENTER] 
		SDB_USER = 'uspace' 
		SDB_PASS = 'uspace.yoka.com' 
		SDB_SERVER = '10.0.10.26:6935' 
		SDB_DB = 'uspace_ucenter' 

[COLLECTIBLE] 
		MDB_USER = 'card_game' 
		MDB_PASS = 'dgfhM367' 
		MDB_SERVER = '10.0.0.24:6919' 
		MDB_DB = 'collectible_card_game' 

[RELATION] 
		MDB_USER = 'mspace' 
		MDB_PASS = 'ffAjKgtTjrb7epIDMS6eNE1zP5' 
		MDB_SERVER = '10.0.0.25:6912' 
		MDB_DB = 'relation' 

[PRODUCTSHARE] 
		MDB_USER = 'share' 
		MDB_PASS = 'shareFR*F3' 
		MDB_SERVER = '10.0.0.22:6914' 
		MDB_DB = 'product_share' 

[FLOWER] 
		MDB_USER = 'flower_game' 
		MDB_PASS = 'fgjrMPCd6' 
		MDB_SERVER = '10.0.0.24:6919' 
		MDB_DB = 'flower_game' 

[GAME] 
		MDB_USER = 'game_center' 
		MDB_PASS = 'Tlkalmnfh7' 
		MDB_SERVER = '10.0.0.24:6919' 
		MDB_DB = 'game_center' 

[GIFT] 
		MDB_USER = 'gifts' 
		MDB_PASS = 'dgfhrhn464367' 
		MDB_SERVER = '10.0.0.24:6913' 
		MDB_DB = 'gifts' 

[ACTION] 
		MDB_USER = 'mspace' 
		MDB_PASS = 'ffAjKgtTjrb7epIDMS6eNE1zP5' 
		MDB_SERVER = '10.0.0.25:6912' 
		MDB_DB = 'action' 

[SPACEVOTE] 
		MDB_USER = 'vote' 
		MDB_PASS = 'ffAjKgtTjrb7epIDMS6eNE1zP5' 
		MDB_SERVER = '10.0.0.24:6913' 
		MDB_DB = 'vote' 

[MESSAGE] 
		MDB_USER = 'mspace' 
		MDB_PASS = 'ffAjKgtTjrb7epIDMS6eNE1zP5' 
		MDB_SERVER = '10.0.0.25:6912' 
		MDB_DB = 'message' 

[STAR] 
		MDB_USER = 'star' 
		MDB_PASS = 'starFR*F3' 
		MDB_SERVER = '10.0.0.22:6914' 
		MDB_DB = 'star' 

[SPECIALACTIVITY] 
		MDB_USER = 'activity_user' 
		MDB_PASS = 'Hrz4mNcTJTHIMUDZQkBkYciMt' 
		MDB_SERVER = '10.0.0.22:6914' 
		MDB_DB = 'special_activity' 

[ASTRO] 
		MDB_USER = 'astro' 
		MDB_PASS = '3cdTLfDf5#' 
		MDB_SERVER = '10.0.0.23:6917' 
		MDB_DB = 'astro' 

/*
[CLUBS] 
		MDB_USER = 'uspace' 
		MDB_PASS = 'uspace.yoka.com' 
		MDB_SERVER = '10.0.0.27:6924' 
		MDB_DB = 'clubs' 

*/
[IDEA] 
		MDB_USER = 'idea2301' 
		MDB_PASS = '3jZH8dPwoecoitYTH8t4UHeRPTIp' 
		MDB_SERVER = '10.0.0.23:6917' 
		MDB_DB = 'idea' 

[ITEST] 
		MDB_USER = 'itest231' 
		MDB_PASS = 'caOckVU36CUcKHmpp6Moqj1i2sn3' 
		MDB_SERVER = '10.0.0.23:6917' 
		MDB_DB = 'itest' 

[WEALTH] 
		MDB_USER = 'wealth' 
		MDB_PASS = 'cwVpZxwQ06UsKhWvXWuPq9X5' 
		MDB_SERVER = '10.0.0.23:6917' 
		MDB_DB = 'wealth' 

[STARREVIEW] 
		MDB_USER = 'star_review' 
		MDB_PASS = 'dguf3df6h' 
		MDB_SERVER = '10.0.0.24:6913' 
		MDB_DB = 'star_review' 

[TRY] 
		MDB_USER = 'try' 
		MDB_PASS = 'cwpxw0W9X5' 
		MDB_SERVER = '10.0.0.24:6913' 
		MDB_DB = 'try' 

[NEWTRY] 
		MDB_USER = 'try' 
		MDB_PASS = 'cwpxw0W9X5' 
		MDB_SERVER = '10.0.0.24:6913' 
		MDB_DB = 'newtry' 

/*
[TEST] 
		MDB_USER = 'uspace' 
		MDB_PASS = 'uspace.yoka.com' 
		MDB_SERVER = '10.0.0.27:6924' 
		MDB_DB = 'test' 

*/
[VISITOR] 
		MDB_USER = 'mspace' 
		MDB_PASS = 'ffAjKgtTjrb7epIDMS6eNE1zP5' 
		MDB_SERVER = '10.0.0.25:6912' 
		MDB_DB = 'visitor' 

[ANSWER] 
		MDB_USER_QUESTION = 'question_answer' 
		MDB_PASS_QUESTION = 'fdg4fg0' 
		MDB_SERVER_QUESTION = '10.0.0.22:6914' 
		MDB_DB_QUESTION = 'question_answer' 

[NEWSUM] 
		MDB_USER = 'yokacms' 
		MDB_PASS = 'yvch500M' 
		MDB_SERVER = '10.0.0.122:3307' 
		MDB_DB = 'NewsSum' 

[AIMU] 
		MDB_USER_ZHUANTI = 'zhuanti' 
		MDB_PASS_ZHUANTI = 'zhuanti.yoka.com' 
		MDB_SERVER_ZHUANTI = '10.0.0.22:6914' 
		MDB_DB_ZHUANTI = 'zhuanti_aimu' 

[USERLOG] 
		MDB_USER = 'uspace' 
		MDB_PASS = 'uspace.yoka.com' 
		MDB_SERVER = '10.0.0.21:3306' 
		MDB_DB = 'userlog' 

[USERLOG] 
		SDB_USER = 'uspace' 
		SDB_PASS = 'uspace.yoka.com' 
		SDB_SERVER = '10.0.0.21:3306' 
		SDB_DB = 'userlog' 

[MIMI] 
		MDB_USER = 'mimi' 
		MDB_PASS = 'mimi.yoka.com' 
		MDB_SERVER = '10.0.0.21:3306' 
		MDB_DB = 'mimi' 

#销售专题的配置
[ZHUANTI] 
		MDB_USER_SALE = 'sale_zhuanti' 
		MDB_PASS_SALE = '4yffDqvkp9' 
		MDB_SERVER_SALE = '10.0.0.30:6925' 
		MDB_DB_SALE = 'sale_zhuanti' 

# 消息通知系统的配置
[SYSNOTICE] 
		MDB_USER = 'sys_notice' 
		MDB_PASS = 'sys_notice.yoka.com' 
		MDB_SERVER = '10.0.0.21:3306' 
		MDB_DB = 'yoka_sys_notice' 

[SYSNOTICE] 
		SDB_USER = 'sys_notice' 
		SDB_PASS = 'sys_notice.yoka.com' 
		SDB_SERVER = '10.0.0.21:3306' 
		SDB_DB = 'yoka_sys_notice' 

# 主题导购
[SHOPPING] 
		MDB_USER = 'shopping' 
		MDB_PASS = 'goshop.yoka.com' 
		MDB_SERVER = '10.0.0.22:6914' 
		MDB_DB = 'shopping' 

// 互动的职业工作
[USPACEJOB] 
		MDB_SERVER = '10.0.10.21:6934' 
		MDB_USER = 'uspace' 
		MDB_PASS = 'uspace.yoka.com' 
		MDB_DB = 'uspace_job' 

[USPACEJOB] 
		SDB_SERVER = '10.0.10.22:6934' 
		SDB_USER = 'uspace' 
		SDB_PASS = 'uspace.yoka.com' 
		SDB_DB = 'uspace_job' 

#论坛
[DISCUZ] 
		SDB_USER = 'uspace' 
		SDB_PASS = 'uspace.yoka.com' 
		SDB_SERVER = '10.0.0.31:6916' 
		SDB_DB = 'discuz' 

#互动任务系统
[UTASK] 
		MDB_USER = 'uspace' 
		MDB_PASS = 'uspace.yoka.com' 
		MDB_SERVER = '10.0.10.21:6934' 
		MDB_DB = 'utask' 

#资讯的秀场频道
[SHOW] 
		MDB_USER = 'uspace' 
		MDB_PASS = 'uspace.yoka.com' 
		MDB_SERVER = '10.0.10.21:6934' 
		MDB_DB = 'show' 

#拜时尚的配置
[BY] 
		MDB_USER = 'uspace' 
		MDB_PASS = 'uspace.yoka.com' 
		MDB_SERVER = '10.0.10.21:6934' 
		MDB_DB = 'by' 

# 站点向导系统
[SITEGUIDE] 
		MDB_SERVER = '10.0.0.22:6914' 
		MDB_USER = 'site_guide' 
		MDB_PASS = 'site_guide.yoka.com' 
		MDB_DB = 'site_guide' 

[THEME] 
		MDB_USER_YOKACMS = 'yokacms_theme' 
		MDB_PASS_YOKACMS = 'fgQLgjSPe' 
		MDB_SERVER_YOKACMS = '10.0.10.25:6935' 
		MDB_DB_YOKACMS = 'yokacms_theme' 

# seo采集百度关键字搜狐滚动新闻
[SEOFETCH] 
		MDB_USER = 'seofetch' 
		MDB_PASS = 'seofetch.app.yoka.com' 
		MDB_SERVER = '10.0.0.23:6917' 
		MDB_DB = 'seofetch' 

#SEO_KEYOWRDS关键字连接
[KEYWORDS] 
		MDB_USER_SEO = 'keywords' 
		MDB_PASS_SEO = 'keywords_yoka_com' 
		MDB_SERVER_SEO = '10.0.0.16:6938' 
		MDB_DB_SEO = 'seo_keywords' 

[KEYWORDS] 
		SDB_USER_SEO = 'keywords' 
		SDB_PASS_SEO = 'keywords_yoka_com' 
		SDB_SERVER_SEO = '10.0.0.16:6938' 
		SDB_DB_SEO = 'seo_keywords' 

# YOKA资讯内容整合
[NEWS] 
		MDB_USER = 'yoka_article' 
		MDB_PASS = 'yoka_article.yoka.com' 
		MDB_SERVER = '10.0.0.22:6915' 
		MDB_DB = 'yoka_article' 

# 品牌新闻
[BRANDNEWS] 
		MDB_USER = 'brandnews' 
		MDB_PASS = 'brandnews.yoka.com' 
		MDB_SERVER = '10.0.10.21:6934' 
		MDB_DB = 'brand_news' 

#memcache
[COMMON] 
		CACHE_HOST = '10.0.0.211' 
		CACHE_PORT = '11211' 

[VISITOR] 
		CACHE_HOST = '10.0.0.211' 
		CACHE_PORT = '11212' 

[MESSAGE] 
		CACHE_HOST = '10.0.0.211' 
		CACHE_PORT = '11213' 

[ACTION] 
		CACHE_HOST = '10.0.0.211' 
		CACHE_PORT = '11214' 

[TRY] 
		CACHE_HOST = '10.0.0.211' 
		CACHE_PORT = '11217' 

