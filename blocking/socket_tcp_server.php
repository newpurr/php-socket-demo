<?php

include_once '../helper/helper.php';

// 阻塞IO服务端
// tcp socket服务端分需要实现以下几步
// 1. 创建socket
// 2. 给socket绑定IP:端口
// 3. 监听socket
// 4. 接收处理请求
//      4.1 accept请求【阻塞,直到有连接时进程才会恢复成非阻塞态,然后执行下边的逻辑】
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

fwrite(STDOUT,'请输入监听的端口：');
$port = fgets(STDIN);

// 二、给套接字绑定ip+port
$port = (int)$port ? : 8090;
$ret = socket_bind($socket, '0.0.0.0', $port);
if (!$ret) {
    die('bind server fail');
}

// 三、监听socket上的连接
$ret = socket_listen($socket, 2);
if (!$ret) {
    die('listen server fail');
}
echo "listening port: {$port}，waiting client...\n";

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
    
    echo "client {$conn} connect succ.\n";
    // 四、接收请求、返回响应、关闭连接
    // onRecv($conn);
    http_protocol_handle($conn);
}


// 关闭socket
socket_close($socket);

function http_protocol_handle($conn) {
    // 读取请求数据
    $buffer = socket_read($conn, 1024);
    if (strpos($buffer, '/favicon.ico')) {
        // 关闭连接
        socket_write($conn, '');
        socket_close($conn);
        return;
    }
    echo "http 请求体原始数据.\n";
    var_dump($buffer);
    
    // 解析http请求体
    $httpData = parse_http_protocol($buffer);
    var_dump($httpData);
    
    // 构建uri处理业务逻辑
    $filename = ltrim($httpData['request_uri'], '/');
    $filename = (!file_exists('./' . $filename) || !$filename)
                    ? './404.html'
                    : './' . $filename;
    
    // 构建http响应体
    $resopnse = build_http_protocol_res(file_get_contents($filename));
    echo "http 响应体.\n";
    var_dump($resopnse);
    
    // 返回给客户端
    socket_write($conn, $resopnse);
    
    // 关闭连接
    socket_close($conn);
}

/**
 * 解析客户端消息
 * 协议：换行符(\n)
 *
 * @param $conn
 */
function onRecv($conn)
{
    socket_write($conn, "hello client {$conn}\n");
    echo "waiting to receive data\n";
    
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
