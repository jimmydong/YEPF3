# YEJS  （基于Vue的快速前端框架）

此框架用于配合YEPF进行快速开发

JS部分可参考： https://github.com/jimmydong/yejs-init

## 快速使用说明

### 第一步 数据准备

创建controller/action，书写基础逻辑，数据封装至 $response->data， 调用 $this->display 输出。

默认使用common模板，无需修改。

### 第二步 创建模板、JS

在 WEBROOT/yejs/page 下创建 [controller] 目录

创建 [action].html ， 书写主体内容

创建 [action].js ， 书写主JS

### 第三步 页面访问调试

检查并调试，页面是否正常工作

### 第四部 编译

编译准备：安装 node.js

WEBROOT/yejs/page 下， ./build.sh [controller] [action] 进行编译

修改 controller/action 文件， 增加： $response->build = true

或者，在 basecontroller 中， 设定 $_hasBuild

## 进阶使用

### 自定义模板

如果有特殊需要，common模板不能满足，可以在调用 display 时指定模板

### 路由功能

待补充。。。
