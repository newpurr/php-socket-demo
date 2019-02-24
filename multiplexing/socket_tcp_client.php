<?php
// tcp socket客户端分需要实现以下几步
// 1. 创建socket
// 2. 使用socket连接指定的server IP:端口【可省略】
// 3. connect服务端
// 4. 发送请求，接收响应
// 5. 关闭socket 连接

// 创建连接
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$socket) {
    die('create server fail:');
}

// 绑定socket ip和端口
// $ret = socket_bind($socket, '0.0.0.0', 80902);
// if (!$ret) {
//     die('bind server fail');
// }

// 连接server
$response = socket_connect($socket, '127.0.0.1', 80925);
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
