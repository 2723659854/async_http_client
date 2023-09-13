<?php
/** 本地服务器地址 */
$server = 'tcp://0.0.0.0:8686';
/** 需要请求的对端服务器地址 */
$remoteAddress = 'tcp://127.0.0.1:8000';
/** 对端服务器host，域名 */
$remoteHost = '127.0.0.1';
/** 对端服务器接口，这个接口很耗时，假设10秒吧 */
$api = '/api/test/publish_rabbitmq';
