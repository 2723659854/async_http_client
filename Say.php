<?php
use Workerman\Events\EventInterface;
use Workerman\Worker;

class Say
{
    /** 连接成功响应事件 */
    public $onConnect;
    /** 接收消息响应事件 */
    public $onMessage;
    /** 客户端关闭响应事件 */
    public $onClose;
    /** 发生错误响应事件 */
    public $onError;
    /** http服务器地址 */
    public $address;
    /** 客户端连接 */
    public $client = null;

    public function __construct($address)
    {
        /** 一步到位，直接设置建立连接的地址 */
        $this->address=$address;
    }

    /**
     * 建立连接
     * @return void
     */
    public function connect()
    {
        /** 首先是创建一个异步的客户端 */
        $this->client = \stream_socket_client($this->address, $errno, $errstr, 0,
            \STREAM_CLIENT_ASYNC_CONNECT);
        /** epoll的write事件是指在epoll监听的文件描述符上有可写数据可以读取。当一个文件描述符上有数据可写时，epoll会向应用程序发送一个write事件，通知应用程序可以进行写操作了。 */
        /** 使用globalEvent->add把客户端连接注册到epoll多路io复用模型当中，注册监听write可写事件（就是监听发现客户端是可写的，就会触发回调），并设置回调 */
        Worker::$globalEvent->add($this->client, EventInterface::EV_WRITE, array($this, 'checkConnection'));
        /** 如果是windows，则需要再注册一个except事件，并设置回调 */
        if (\DIRECTORY_SEPARATOR === '\\') {
            Worker::$globalEvent->add($this->client, EventInterface::EV_EXCEPT, array($this, 'checkConnection'));
        }
    }

    /**
     * 检查连接
     * @return void
     * @note 这里并没有检查连接，为了模拟，直接调用用户定义的onConnect事件
     */
    public function checkConnection()
    {
        /** 如果是windows 删除except事件监听 */
        if (\DIRECTORY_SEPARATOR === '\\') {
            Worker::$globalEvent->del($this->client, EventInterface::EV_EXCEPT);
        }

        /** 删除监听可写事件 */
        Worker::$globalEvent->del($this->client, EventInterface::EV_WRITE);

        /** 设置非阻塞状态 */
        if(!stream_set_blocking($this->client, false)) {
            if ($this->onError){
                \call_user_func($this->onError,  new Exception('设置流为非阻塞状态失败'));
            }else{
                $this->error('设置流为非阻塞状态失败');
            }
        }
        /** 如果用户定义了onConnect属性，那么就调用用户的函数 */
        if ($this->onConnect) {
            \call_user_func($this->onConnect, $this);
        }

    }

    /**
     * 发送数据给服务器
     * @param string $url 请求的接口
     * @param float $timeout 设置超时
     * @return void
     */
    public function send($url,$timeout=0.5)
    {
        /** 调用client请求这个接口 ，然后如果返回正确结果调用onmessage ,否则调用error*/
        if (false === stream_socket_sendto($this->client, $url)) {
            if ($this->onError){
                \call_user_func($this->onError, new Exception("发送数据失败"));
            }else{
                $this->error("发送数据失败");
            }
        }else{
            $timeout = $timeout*1000;
            while($timeout){
                $data= \stream_get_contents($this->client);
                if ($data){
                    /** 使用 request类解析数据 */
                    $request = new \Workerman\Protocols\Http\Request($data);
                    $this->message($request);
                    return;
                }
                $timeout--;
                usleep(1);
            }
            if ($this->onError){
                \call_user_func($this->onError,  new Exception("请求超时"));
            }else{
                $this->error("http请求超时");
            }
        }
    }

    /**
     * 怎么触发这个onmessage
     * @param $data
     * @return void
     */
    public function message($data)
    {
        /** 模仿耗时业务 */
        call_user_func($this->onMessage, $data);
    }

    /**
     * 关闭客户端
     * @return void
     */
    public function close()
    {
        /** 关闭客户端 */
        fclose($this->client);
        /** 释放资源 */
        $this->client = null;
        /** 调用自定义的回调函数 */
        if (is_callable($this->onClose)){
            call_user_func($this->onClose, []);
        }
    }

    /**
     * 处理异常信息
     * @param $message
     * @return void
     */
    public function error($message)
    {
        echo "{$message}\r\n";
        Worker::stopAll();
    }

}