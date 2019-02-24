<?php

// 非阻塞IO服务端
// tcp socket服务端分需要实现以下几步
// 1. 创建socket
// 2. 设置socket为非阻塞模式
// 3. 给socket绑定IP:端口
// 4. 监听socket
// 5. 接收处理请求
//      4.1 accept请求【非阻塞】
//      4.2 read数据 & write数据
//      4.3 关闭accept的链接
// 6.关闭socket(关闭服务)

// 创建socket
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$socket) {
    die('create server fail');
}

// 设置socket为非阻塞模式
socket_set_nonblock($socket);

// 给套接字绑定ip+port
$ret = socket_bind($socket, '0.0.0.0', 8093);
if (!$ret) {
    die('bind server fail');
}

// 监听socket上的连接
$ret = socket_listen($socket, 2);
if (!$ret) {
    die('listen server fail');
}
echo "waiting client...\n";


while (true) {
    // 非阻塞accept等待客户端连接
    // 如果没有成功accept到客户端，accept将返回false,我们手动将进程睡眠2秒,然后再次accept(也可以不sleep,但是容易cpu100%)
    // 如果accept成功,我们将连接交给onRecv处理
    $conn = socket_accept($socket);
    var_dump($conn);
    if (!$conn) {
        echo 'accept server fail,但是我没被阻塞住哦';
        sleep(2);
        continue;
    }
    
    echo "client connect succ.\n";
    // 接收请求、返回响应、关闭连接
    onRecv($conn);
}

echo 'bye';


// 关闭socket
socket_close($socket);

/**
 * 解析客户端消息
 * 协议：换行符(\n)
 *
 * @param $conn
 */
function onRecv($conn)
{
    //实际接收到的消息
    $recv = '';
    
    //循环读取消息
    while (true) {
        $buffer = socket_read($conn, 100); //每次读取100byte
        if ($buffer === false || $buffer === '') {
            echo "client closed\n";
            //关闭本次连接
            socket_close($conn);
            break;
        }
        
        //解析单次消息，协议：换行符
        $pos = strpos($buffer, "\n");
        
        //消息未读取完毕，继续读取
        if ($pos === false) {
            $recv .= $buffer;
        }
        
        //消息读取完毕
        else {
            //去除换行符及空格
            $recv .= trim(substr($buffer, 0, $pos + 1));
            
            //客户端主动端口连接,关闭本次连接
            if ($recv === 'quit') {
                exit('server closed');
                break;
            }
            
            // 响应数据
            echo "recv: $recv \n";
            socket_write($conn, "hello this is server, you send data:$recv \n");
    
            //清空消息，准备下一次read(连接不是单次处理完就关闭)
            $recv = '';
        }
    }
}
