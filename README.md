# opsLogSdk

#安装方式
composer require "steven-yx/opsLogSdk @dev"

#使用方式

$operator=[
    'id'=>'',
    'name'=>'',
    'mobile'=>'',
    'ip'=>'127.0.0.1'
 ];
#初始化
$this->opslog=(new OpsLog\OpsClient($url,$appid)->initOperator($operator);
 
#添加日志
$this->opslog->save($biz,$biz_id,$desc,$param);
