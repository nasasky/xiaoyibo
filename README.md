# 笑一波api源码使用说明

1. 基于tp5.1开发，若不熟悉，请自行参考tp5.1官方文档
2. 使用前建立一个空白数据库apicom，将源码目录下的apicom.sql导入此数据库;然后到/config/database.php中修改数据库信息
3. 到/application/syj/controller/index.php 修改`https://api.xf512.com/`为你的域名
4. 源码中不含有图片 视频等静态资源信息，建议清空数据库信息，重新填充或采集
5. 根目录指向到public
6. runtime赋予777权限
7. 投稿审核原理为：投稿后的默认状态为待审核，即api_thread表status字段值设为0，将status值改为1，即为审核通过 审核管理模块请照上述原理自行实现

## 笑一波app源码地址 

[https://gitee.com/jameson512/xiaoyibo](https://gitee.com/jameson512/xiaoyibo)