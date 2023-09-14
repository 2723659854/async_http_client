分析workman异步请求实现原理
### 安装
 直接拉最新代码就行
 ```bash 
 git clone git@github.com:2723659854/async_http_client.git
 ```
### 配置

注意修改config.php里面http服务监听的端口，需要请求的服务器ip,host,port,api

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
#### 拓展
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;异步请求的实现原理是将客户端连接投递到系统的事件模型中。
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;常用的事件模型是select和epoll。当投递到事件模型中后，请求其实就和当前进程没有多大关系了。<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;首先select是posix支持的，而epoll是linux特定的系统调用，因此，epoll的可移植性就没有select好，但是考虑到epoll和select一般用作服务器的比较多，而服务器中大多又是linux，所以这个可移植性的影响应该不会很大。<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;其次，select可以监听的文件描述符有限，最大值为1024，而epoll可以监听的文件描述符则是系统对整个进程限制的最大文件描述符。<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;接下来就要谈epoll和select的性能比较了，这个一般情况下应该是epoll表现好一些，否则linux也不会去特定实现epoll函数了，那么epoll为什么比select更高效呢？<br>
原因有很多，第一点，epoll通过每次有就绪事件时都将其插入到一个就绪队列中，使得epoll_wait的返回结果中只存储了已经就绪的事件，而select则返回了所有被监听的事件，事件是否就绪需要应用程序去检测，那么如果已被监听但未就绪的事件较多的话，对性能的影响就比较大了。第二点，每一次调用select获得就绪事件时都要将需要监听的事件重复传递给操作系统内核，而epoll对监听文件描述符的处理则和获得就绪事件的调用分开，这样获得就绪事件的调用epoll_wait就不需要重新传递需要监听的事件列表，这种重复的传递需要监听的事件也是性能低下的原因之一。除此之外，epoll的实现中使用了mmap调用使得内核空间和用户空间共享内存，从而避免了过多的内核和用户空间的切换引起的开销。<br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;然后就是epoll提供了两种工作模式，一种是水平触发模式，这种模式和select的触发方式是一样的，要只要文件描述符的缓冲区中有数据，就永远通知用户这个描述符是可读的，这种模式对block和noblock的描述符都支持，编程的难度也比较小；而另一种更高效且只有epoll提供的模式是边缘触发模式，只支持nonblock的文件描述符，他只有在文件描述符有新的监听事件发生的时候（例如有新的数据包到达）才会通知应用程序，在没有新的监听时间发生时，即使缓冲区有数据（即上一次没有读完，或者甚至没有读），epoll也不会继续通知应用程序，使用这种模式一般要求应用程序收到文件描述符读就绪通知时，要一直读数据直到收到EWOULDBLOCK/EAGAIN错误，使用边缘触发就必须要将缓冲区中的内容读完，否则有可能引起死等,尤其是当一个listen_fd需要监听到达连接的时候，如果多个连接同时到达，如果每次只是调用accept一次，就会导致多个连接在内核缓冲区中滞留，处理的办法是用while循环抱住accept，直到其出现EAGAIN。这种模式虽然容易出错，但是性能要比前面的模式更高效，因为只需要监听是否有事件发生，发生了就直接将描述符加入就绪队列即可。<br>

