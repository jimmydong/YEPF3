YEPF3 PHP快速开发框架第三版
===================================

YEPF 3.0 BETA
------------------

#主要改进内容：
+ 公用类放置到yoka命名空间，避免与其他项目冲突。（例如： Debug）
+ 全局公用函数封装到静态类Utility (仍有少量未收纳，参见function.inc.php)
+ 整合spritMVC到基础功能
+ DB默认采用pdo模式 （仍支持非pdo，不建议）
+ 提供了完成的项目框架建议（DEMO）
+ 配置文件采用ini格式

#DEMO中提供的内容
+ 目录结构建议
+ 多项目的根目录、controller、templete共存
+ 配置文件引入方式及本地环境配置方式
+ MongoDB操作类（待加入基础功能）

#旧版本迁移需进行的处理
+ 修改ENV配置文件为新格式 （参考转化工具：demo/DocumentRoot/tools/ENV2to3.php）
+ 修改全部Debug,DB,Cache等公用类为 yoka\Debug,yoka\DB,yoka\Cache等域名空间调用方式
+ 使用spriteMVC的，参考demo中使用方式进行命名空间的调整


YEPF 3.0 的使用
-------------------

#已有项目将YEPF3作为脚手架
仅需引用global.inc.php即可

#作为新项目框架使用
参考DEMO，按如下步骤配置：
+ 编译PHP，增加mysql-pdo,memcached等必要模块
+ 编写ENV文件
+ 部署目录结构
+ 编写_LOCAL/local.inc.php进行本地化配置
+ 调整apache配置指向DocumentRoot目录
+ 编写controller
+ 编写template
+ 使用SVN时，请将_LOCAL,_TEMPLATE_C,_LOG目录排除



