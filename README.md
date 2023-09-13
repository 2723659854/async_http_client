分析workman异步请求实现原理
### 安装
 直接拉最新代码就行
 ```bash 
 git clone git@github.com:2723659854/async_http_client.git
 ```
### 配置

注意修改index.php里面http服务监听的端口，需要请求的服务器ip,host,port,api

```php
/** 本地服务器地址 */
$server = 'tcp://0.0.0.0:8686';
/** 需要请求的对端服务器地址 */
$remoteAddress = 'tcp://127.0.0.1:8000';
/** 对端服务器host，域名 */
$remoteHost = '127.0.0.1';
/** 对端服务器接口，这个接口很耗时，假设10秒吧 */
$api = '/api/test/publish_rabbitmq';
```
### 运行
```bash 
php ./index.php
```
### 访问
http://127.0.0.1:8686/

