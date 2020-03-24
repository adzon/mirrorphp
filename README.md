# 镜像系统客户端

本客户端是镜像系统黑五类专版的客户端程序。

如需帮助请联系 QQ 76762

功能：

1. Cloak 的自动对接
2. 通过镜像的方式制作安全页
3. 通过 Cloak 检测之后输出 Landing Page
4. 替换 Landing Page 中的对应标签

# 安装方式

基于服务环境的不同，本文件有2种安装方式

## Laravel Forge

进入  Server -> Site

选择通过 Git 仓库安装

![选择通过 Git 仓库安装](https://s3.us-east-2.amazonaws.com/affcool/screenshot/2020-03-24-061422.png)

填写本仓库地址

![填写本仓库](https://s3.us-east-2.amazonaws.com/affcool/screenshot/2020-03-24-061703.png)

编辑环境配置

![编辑环境配置](https://s3.us-east-2.amazonaws.com/affcool/screenshot/2020-03-24-062109.png)

填入你的镜像地址

![填入你的镜像地址](https://s3.us-east-2.amazonaws.com/affcool/screenshot/2020-03-24-062247.png)


## 其他服务器环境

修改 index.php 的 $server 地址为你的镜像地址

文件放到服务器的默认站点目录即可。
