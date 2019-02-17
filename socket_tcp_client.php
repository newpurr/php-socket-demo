<?php

// tcp socket服务端分需要实现以下几步
// 1. 创建socket
// 2. 使用socket连接指定的server IP:端口
// 3. 监听socket
// 4. 接收处理请求
//      4.1 accept请求
//      4.2 read数据 & write数据
//      4.3 关闭accept的链接
// 5.关闭socket(关闭服务)

// 创建连接
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$socket) {
    die('create server fail:');
}

// 连接server
$response = socket_connect($socket, '127.0.0.1', 8090);
if (!$response) {
    die('connect fail');
}

while (true) {
    // 发送消息
    echo '请输入您要发送的内容' . PHP_EOL;
    $str = trim(fgets(STDIN));
    socket_write($socket, $str . "\n");
    
    // 读取消息
    $recv = '';
    while (true) {
        $buffer = socket_read($socket, 1024);
        if ($buffer === false) {
            socket_close($socket);
            exit('read socket error' . "\n");
        }
        
        //解析单次消息，协议：换行符
        $pos = strpos($buffer, "\n");
    
        // 消息未读取完毕，继续读取
        if ($pos !== false) {
            break;
        }
        
        $recv .= $buffer;
    }
    echo "server response: {$buffer} \n";
    
    if (strpos($buffer, 'bye')) {
        break;
    }
}

// 关闭连接
socket_close($socket);
