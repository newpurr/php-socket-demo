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
