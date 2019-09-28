<?php

// 安装libevent
// git clone https://github.com/expressif/pecl-event-libevent.git
// cd pecl-event-libevent/
// phpize
// ./configure
// make && sudo make install
// cat "extension=libevent.so" >> /usr/local/php7.2.2/etc/php.ini


/**
 * Class Server
 *
 * @version 1.0
 * @package libevent
 */
class LibEventTcpServer
{
    /**
     * @var bool|resource
     */
    protected $eventBase;
    
    /**
     * @var resource[]
     */
    protected $event;
    
    /**
     * @var resource
     */
    protected $listenFd;
    
    /**
     * @var resource[]
     */
    protected $clientFdArr;
    
    /**
     * Server constructor.
     *
     * @param int $port
     */
    public function __construct($port = 6607)
    {
        $this->eventBase = event_base_new();
        $this->listenFd  = stream_socket_server(sprintf('tcp://0.0.0.0:%d', $port), $errno, $errstr);
        if (false === $this->listenFd) {
            throw new RuntimeException('stream_socket_server init error');
        }
        stream_set_blocking($this->listenFd, 0);
    }
    
    public function listen() : void
    {
        $event = event_new();
        // 设置 event：其中$events设置为EV_READ | EV_PERSIST
        // event 指示期望事件的一组标志可以是EV_READ和/或EV_WRITE。
        // 附加标志EV_PERSIST使事件持续直到调用event_del（），否则仅调用一次回调。
        // 可以让注册的事件在执行完后不被删除,直到调用event_del()删除.
        event_set($event, $this->listenFd, EV_READ | EV_PERSIST, [$this, 'onAccept'], $this->eventBase);
        event_base_set($event, $this->eventBase);
        event_add($event);
        echo 'server run in port 6607 ...' . PHP_EOL;
    
        $this->event[(int) $this->listenFd] = $event;
        
        //进入事件循环
        event_base_loop($this->eventBase);
    }
    
    /**
     * @param resource $listenFd
     * @param int      $events
     * @param resource $eventBase
     */
    protected function onAccept($listenFd, $events, $eventBase) : void
    {
        if ( !($events & EV_READ)) {
            return;
        }
        
        $fd = stream_socket_accept($listenFd);
        if (false === $fd) {
            throw new RuntimeException('stream_socket_accept init error');
        }
        $id = (int)$fd;
        
        echo "accept a new client $id\n";
        
        $event = event_new();
        event_set($event, $fd, EV_READ | EV_PERSIST, [$this, 'onRead']);
        event_base_set($event, $this->eventBase);
        event_add($event);
    
        $this->event[(int) $fd] = $event;
        
        $this->clientFdArr[$id] = $fd;
    }
    
    /**
     * @param resource $fd
     */
    protected function onRead($fd) : void
    {
        // var_dump(func_get_args());
        // var_dump($fd);
    
        $content = '';
        
        while (1) {
            $read = @fread($fd, 1024);
    
            if($read === '' || $read === false) {
                echo sprintf("client[fd=%s]  closed \n", $fd);
                fclose($fd);
                unset($this->clientFdArr[(int)$fd], $this->event[(int)$fd]);
                break;
            }
            
            $pos = strpos($read, "\n");
            if ($pos === false) {
                $content .= $read;
            } else {
                $content .= trim(substr($read, 0, $pos + 1));
                
                echo sprintf("hello:%s \n", $content);
                fwrite($fd, sprintf("hello:%s \n", $content));
                $content = '';
            }
        }
    }
}

$server = new LibEventTcpServer();
$server->listen();
