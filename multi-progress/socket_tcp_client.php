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
$response = socket_connect($socket, 'www.abcode.club', 80);
if (!$response) {
    die('connect fail');
}

while (true) {
    // 发送消息
    // echo '请输入您要发送的内容' . PHP_EOL;
    
    // $str = trim(fgets(STDIN));
    $str = "GET / HTTP/1.1\r\nHost: www.abcode.club\r\nUser-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/73.0.3683.86 Safari/537.36\r\nAccept: text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8,application/signed-exchange;v=b3\r\n\r\n";
    socket_write($socket, $str . "\n");
    
    // 读取消息
    $recv = '';
    while (true) {
        $buffer = socket_read($socket, 102400);
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
    // echo "server response: {$buffer} \n";
    echo $recv;
    ob_flush();
    flush();
    
    if (strpos($buffer, 'bye')) {
        break;
    }
    sleep(10);
    $recv = '';
}

// 关闭连接
socket_close($socket);
