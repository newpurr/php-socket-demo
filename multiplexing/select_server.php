<?php

// IO多路复用,一个进程/线程可以处理多个连接

// 创建socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$socket) {
    die('create server fail');
}

// 给套接字绑定ip+port
$ret = socket_bind($socket, '0.0.0.0', 80925);
if (!$ret) {
    die('bind server fail');
}

// 监听socket上的连接
$ret = socket_listen($socket, 2);
if (!$ret) {
    die('listen server fail');
}
echo "waiting client...\n";

$readfds     = [];
$writefds    = [];
$exceptfds   = [];
$connections = [];
while (true) {
    $readfds = array_merge($connections, [$socket]);
    
    /*
     * socket_select是阻塞，有数据请求才处理，否则一直阻塞
     * 此处$readfds会读取到当前活动的连接
     * 比如执行socket_select前的数据如下(描述socket的资源ID)：
     * $socket = Resource id #4
     * $readfds = Array
     *       (
     *           [0] => Resource id #5 //客户端1
     *           [1] => Resource id #4 //server绑定的端口的socket资源
     *       )
     * 调用socket_select之后，此时有两种情况：
     * 情况一：如果是新客户端2连接，那么 $readfds = array([1] => Resource id #4),此时用于接收新客户端2连接
     * 情况二：如果是客户端1(Resource id #5)发送消息，那么$readfds = array([1] => Resource id #5)，用户接收客户端1的数据
     *
     * 通过以上的描述可以看出，socket_select有两个作用，这也是实现了IO复用
     * 1、新客户端来了，通过 Resource id #4 介绍新连接，如情况一
     * 2、已有连接发送数据，那么实时切换到当前连接，接收数据，如情况二
    */
    if (socket_select($readfds, $writefds, $exceptfds, 2)) {
        // 如果是当前服务端的监听连接
        if (in_array($socket, $readfds, true)) {
            echo "socket_accept\n";
            // 接受客户端连接
            $newconn = socket_accept($socket);
            $i       = (int)$newconn;
            
            // 将当前客户端连接放入 socket_select 选择
            $connections[$i] = $newconn;
            
            // 输入的连接资源缓存容器
            $writefds[$i] = $newconn;
            
            echo "Client $i come.\n";
            
            $key = array_search($socket, $readfds, true);
            unset($readfds[$key]);
        }
        // 轮循读通道
        foreach ($readfds as $rfd) {
            // 客户端连接
            $i = (int)$rfd;
            // 从通道读取
            $line = @socket_read($rfd, 2048, PHP_NORMAL_READ);
            if ($line === false) {
                // 读取不到内容，结束连接
                echo "Connection closed on socket $i.\n";
                closeConn($i);
                continue;
            }
            $tmp = substr($line, -1);
            if ($tmp !== "\r" && $tmp !== "\n") {
                // 等待更多数据
                continue;
            }
            // 处理逻辑
            $line = trim($line);
            if ($line === "quit") {
                echo "Client $i quit.\n";
                closeConn($i);
                break;
            }
            if ($line) {
                echo "Client $i >>" . $line . "\n";
                //发送客户端
                socket_write($rfd, "$i=>$line\n");
            }
        }
        
        // 轮循写通道
        foreach ($writefds as $wfd) {
            $i = (int)$wfd;
            socket_write($wfd, "Welcome Client $i!\n");
        }
    }
}

function closeConn($i)
{
    global $connections;
    socket_shutdown($connections[$i]);
    socket_close($connections[$i]);
    unset($connections[$i]);
}
