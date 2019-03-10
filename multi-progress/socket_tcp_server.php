<?php

// 阻塞IO服务端
// tcp socket服务端分需要实现以下几步
// 1. 创建socket
// 2. 给socket绑定IP:端口
// 3. 监听socket
// 4. 接收处理请求
//      4.1 accept请求，将请求交给一个进程进行处理
//      4.2 read数据 & write数据
//      4.3 关闭accept的链接
// 5.关闭socket(关闭服务)

// 一、创建socket
// 参数domain: AF_INET => IPv4 网络协议。TCP 和 UDP 都可使用此协议。
// 参数type: SOCK_STREAM => 提供一个顺序化的、可靠的、全双工的、基于连接的字节流。支持数据传送流量控制机制。TCP 协议即基于这种流式套接字。
// 参数protocol: SOL_TCP => tcp协议
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$socket) {
    die('create server fail');
}

// 二、给套接字绑定ip+port
$ret = socket_bind($socket, '0.0.0.0', 8090);
if (!$ret) {
    die('bind server fail');
}

// 三、监听socket上的连接
$ret = socket_listen($socket, 2);
if (!$ret) {
    die('listen server fail');
}
echo "waiting client...\n";

// 如下死循环实现的accept客户端的模式是同步阻塞的,
// 也就是说这种模式只能处理一个连接,当一个连接未关闭时
// 当前server完全不能处理其他请求
while (true) {
    // 四、阻塞等待accept客户端连接
    $conn = socket_accept($socket);
    if (!$conn) {
        echo 'accept server fail';
        continue;
    }
    
    echo "client connect succ.\n";
    // 四、接收请求、返回响应、关闭连接
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
    //创建子进程
    $pid = pcntl_fork();
    
    //父进程和子进程都会执行下面代码
    if ($pid === -1) {
        //错误处理：创建子进程失败时返回-1.
        echo 'fork 子进程错误';
        return;
    }
    
    if ($pid) {
        // 父进程会得到子进程号，所以这里是父进程执行的逻辑
        // pcntl_wait($status); //等待子进程中断，防止子进程成为僵尸进程。
        socket_close($conn);
    } else {
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
}
