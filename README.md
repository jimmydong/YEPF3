YEPF3 PHP快速开发框架第三版
===================================

```
2017.2.9 重要更新：  鉴于Firebug停止维护，改进为同时支持Firefox Devtool/Firebug+FirePHP/Chrome+ChromeLogger
FirePHP用法不变，在51.0.x之后工作不稳定，不推荐使用，或请使用50.1.0 (http://archive.mozilla.org/pub/firefox/releases/50.1.0/)
Firefox 请安装 extension/YEPF-firefox.xpi扩展
Chrome 请安装 extension/YEPF-chrome.crx扩展，并自行安装ChromeLogger扩展
```

简介：
YEPF（Yam Elastic Php Framework） 是一套PHP快速开发工具，可以简单以脚手架方式介入到已有项目中进行辅助开发，也可以按照提供的规划建议进行全新的项目构架开发，能够与composer或其他开发框架轻松的整合在一起，具有使用灵活、定制性好、延展性强、跟踪调试快捷等特点。

项目最早于SOHU公司形成雏形，在YOKA公司、宜生到家逐渐成熟，目前已有10余年的开发积累，在数十个项目中得到应用，证明从小微项目到大规模异构项目中都可以良好运行并极大提高开发维护效率。

YEPF可以在项目的生存期的任何阶段介入，欢迎使用并提出你的想法和建议。

## 特色功能

- 可实时进行线上调试。
- 调试开启可通过多种方式（比如环境设定或调用参数）。
- 可监控所有DB操作、MONGO操作、Memcached的时间、操作、返回值
- 可设置监控点，监控运行时间、监控点的变量值等
- 多个项目公用基础平台构架
- MySQL的DAO封装中，同时支持直接pdo操作
- 支持以Schema模式操作Mongo

## 主要改进内容：

+ 公用类放置到yoka命名空间，避免与其他项目冲突。（例如： Debug）
+ 全局公用函数封装到静态类Utility (仍有少量未收纳，参见function.inc.php)
+ 整合spritMVC到基础功能
+ DB默认采用pdo模式 （仍支持非pdo，不建议）
+ 提供了完成的项目框架建议（DEMO）
+ 配置文件采用ini格式

#### 已成熟的操作类移入yoka：

    BaseModel ： Mysql DAO 操作类
    BaseMongoRecord ： Mongo DAO 操作类
    Cache : Memcache操作类，兼容： Memecache 与 Memcached
    Cookie: Cookie操作类
    DB ： Mysql PDO 操作类
    Debug ： 调试类（支持在线调试、日志调试、数据调试）
    Queue ： 队列操作类，兼容： Redis 和 SSDB
    SortList ： 排序操作类，兼容： Redis 和 SSDB
    HashSet : 哈希操作类，兼容： Redis 和 SSDB
    Template ： 模板类
    Widget ： 碎片操作类（持久化快速缓冲）

#### 待加入的操作类（项目实践中，代码另见非公开CUSTOM_CLASS项目）

    微信操作相关（多账号支持）
    Swoole操作相关
    文件上传相关
    地图处理相关（百度、谷歌）
    分页与参数传递相关
    HTTP处理相关
    邮件处理相关
    APP开发相关
    ... ...

## DEMO中提供的内容

+ 目录结构建议 _CUSTOM_CLASS/_TEMPLATE/_TEMPALTE_C/_LOG等
+ 配置文件 init.php
+ 外部资源定义 WORK-ENV.ini
+ 本地化配置（多服务器环境差异） _LOCAL
+ 多项目的根目录（DocumentRoot/AdminRoot） 注意：多项目在_TEMPLATE和controller中的目录结构
+ composer引入

## YEPF2旧版本迁移需进行的处理

+ 修改ENV配置文件为新格式 （参考转化工具：demo/DocumentRoot/tools/ENV2to3.php）
+ 修改全部Debug,DB,Cache等公用类为 yoka\Debug,yoka\DB,yoka\Cache等域名空间调用方式
+ 使用spriteMVC的，参考demo中使用方式进行命名空间的调整

## 如何使用YEPF3

####已有项目将YEPF3作为脚手架

+ 最简单的方式: 仅需引用global.inc.php即可
+ 推荐方式： 编写init.php，项目中引用init.php。（实例代码在 armrest 目录中）

####作为新项目框架使用

参考DEMO，按如下步骤配置：
+ 编译PHP，增加mysql-pdo,memcached等必要模块
+ 部署目录结构
+ 编写_LOCAL/local.inc.php进行本地化配置
+ 根据需要调用的外部资源编写WORK-ENV.ini
+ 编写_LOCAL/autoload.inc.php引入WORK-ENV.ini中的资源
+ 调整apache配置指向DocumentRoot目录
+ 在controller目录中编写控制器
+ 在template目录中编写模板
+ 将_TEMPLATE_C/_LOG目录设置为可写
+ 使用SVN时，请将_LOCAL,_TEMPLATE_C,_LOG目录排除

## LICENSE

YEPF3 is released under the [MIT license](https://github.com/jimmydong/YEPF3/blob/master/MIT-LICENSE.txt).



--------
感谢曾为项目编码做出贡献的： 唐劲惟，曹晓冬，王毅，巨建华，李卫卫等。


