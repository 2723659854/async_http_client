<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/Say.php';
require_once __DIR__ . '/config.php';

use Workerman\Worker;
use Workerman\Connection\TcpConnection;

//$worker = new Worker('tcp://0.0.0.0:8686');
$worker = new Worker($server);
$worker->protocol = 'Workerman\\Protocols\\Http';

/** 这里假设就是一个超级简单的http服务器 */
$worker->onMessage = function(TcpConnection $connection, $data)
{
    /** 接收到的参数 */
    $param = new \Workerman\Protocols\Http\Request($data);
    /** 自定义成功回调 */
    $success = function (\Workerman\Protocols\Http\Request $request)use($connection){
       echo $request->rawBody();
       $response =  new \Workerman\Protocols\Http\Response(200,$request->header(),$request->rawBody());
       $connection->send($response);
    };
    /** 自定义失败回调 */
    $fail = function (Exception $exception)use($connection){
        echo date('Y-m-d H:i:s')."发生错误".$exception->getMessage();
        $connection->send(date('Y-m-d H:i:s')."发生错误".$exception->getMessage());
    };
    /** 使用异步请求处理这些参数:客户端连接，客户端参数，成功回调，失败回调 */
    async_request($connection,$param,$success,$fail);
    /** 构建需要返回的内容 */
    $content = file_get_contents(__DIR__.'/index.html');
    /** 构建一个响应 */
    $response = new \Workerman\Protocols\Http\Response(200,$param->header(),$content);
    /** 投递了异步请求任务后，正常返回数据给浏览器,如果这里返回数据给浏览器了，上面设置的成功或者失败回调发送给浏览器的数据，浏览器是收不到的 */
    $connection->send($response);
};

/**
 * 首先是触发onconnect，然后在connect里面调用send方法，send调用massage，message触发onmessage返回消息
 * 异步请求的底层运行逻辑，首先使用host和port创建连接，然后把请求的连接注册到event-loop当中，使用epoll或者select轮询
 * 响应事件把请求变成了异步请求，然后检查到连接可读可写，才发送正式的请求。
 * 然后注册响应事件用的的是Worker::$globalEvent->add方法把连接注册到epoll模型当中，删除使用Worker::$globalEvent->del
 */

/**
 * 异步请求
 * @param TcpConnection $connect http客户端连接 可以使用这个连接向http客户端发送数据 $connect->send($message)
 * @param \Workerman\Protocols\Http\Request $param http客户端发送的request参数 比如获取headers:$param->headers()
 * @return void
 * @note 这里只是一个示例，connect 和 param 参数传递进去后没有使用，你可以根据你自己的需求调用这些参数
 */
function async_request(TcpConnection $connect,\Workerman\Protocols\Http\Request $param,callable $success=null,callable $fail=null){
    include __DIR__ . '/config.php';
    /** 实例化一个异步请求类 ，传递需要请求的服务器地址，这里只能是tcp,udp,ws，txt协议，然后跟上host和端口 */
    $hello = new Say($remoteAddress);

    /** 定义onConnect事件 ，连接成功后将会向服务端发送数据 */
    $hello->onConnect= function ($data)use($hello,$api,$remoteHost){
        echo "发送请求".date('Y-m-d H:i:s');
        echo "\r\n";
        /** 向服务端发送数据 ，其实就是请求的http://127.0.0.1:8000/api/test/publish_rabbitmq 这个接口假设耗时10秒 ，所以需要异步处理 */
        $hello->send("GET {$api} HTTP/1.1\r\nHost: {$remoteHost}\r\nConnection: keep-alive\r\n\r\n");
    };

    /** onMessage事件 这个data是http返回值，需要处理的 */
    $hello->onMessage = function (\Workerman\Protocols\Http\Request $request)use($hello,$success){
        echo "接收到数据".date('Y-m-d H:i:s');
        echo "\r\n";
        ///** 打印对面http服务器接口返回的数据 */
        //echo $request->rawBody();
        #todo 需要自己处理接口返回的数据，rawBody ，然后再执行其他操作，比如调用你自己的回调方法
        if (is_callable($success)){
            \call_user_func($success,$request);
        }
        echo "\r\n";
        /** 一顿操作猛如虎 ，然后关闭 */
        $hello->close();
    };

    /** 定义onClose关闭事件 */
    $hello->onClose = function ($data){
      //echo "http异步客户端关闭\r\n";
    };

    /** 定义onError错误事件 */
    $hello->onError = function(Exception $exception)use($hello,$fail)
    {
        if (is_callable($fail)){
            \call_user_func($fail,$exception);
        }else{
            print_r($exception->getMessage());
        }
        echo "\r\n";
        $hello->close();
    };
    $hello->connect();
}
/** 启动http服务器 */
Worker::runAll();