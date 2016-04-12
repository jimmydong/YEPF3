 * 【注意】建议目录结构
 *  _CUSTOM_CLASS 项目类文件目录
 *  _DOC 文档目录
 *  _LOCAL 保存本地配置信息
 *  _TEMPLATE 放置模板
 *  _TEMPLATE_C 模板编译目录(需可写)
 *  _LOG 日志目录(需可写)
 *  controller 控制器目录
 *  DocumentRoot 网站根目录
 *  AdminRoot 子站点目录
 *  PythonRoot OR Other 协同语言目录
 
原有项目向YEPF3迁移建议：
1，确认PHP版本高于5.3
2，部署/YOKA/CONF/YOKA-ENV-3.0.php （可使用 demo/DocumentRoot/tools/ENV2to3.php转换）
3，搜索代码： \Debug , Debug, \DB, DB, \Template, Template, \Cache, Cache 等原core定义类，
	页面上部对应添加： use yoka\Debug等，代码中去除\。
4，修改_LOCAL/local.inc.php中YEPF定义路径为_YEPF3.0
5，调试运行，针对出现问题参照demo/init.php, demo/_LOCAL/local.inc.php进行修改