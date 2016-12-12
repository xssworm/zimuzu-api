# 字幕组API的PHP SDK #
根据zimuzu.tv公开的API接口实现的PHP SDK。

## 环境要求 ##
* php >= 5.6.3

## 使用方法 ##
```PHP
<?php 

$api = new Api_Zimuzu(your_cid, your_accesskey); //替换成你自己的cid和accesskey 

$data = $api->getTVSchedule('2016-11-20','2016-11-30'); 

var_dump($data);
```
