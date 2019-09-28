<?php declare(strict_types = 1);
// 工具方法来源: https://www.cnblogs.com/k1995/p/5699930.html

const EOL = "\r\n";

if (!function_exists('parse_http_protocol')) {
    
    function parse_http_protocol($raw)
    {
        // 将header分割成数组
        [$httpHeader, $httpBody] = explode("\r\n\r\n", $raw, 2);
        
        $headers = explode("\r\n", $httpHeader);
        if (!$httpHeader || !isset($headers[0])) {
            throw new RuntimeException('不是合法的http协议数据流');
        }
        
        [
            $requestMethod,
            $requestUri,
            $serverProtocol
        ] = explode(' ', $headers[0]);
        unset($headers[0]);
        
        foreach ($headers as $header) {
            // \r\n\r\n
            if (empty($header)) {
                continue;
            }
            [$key, $value] = explode(':', $header, 2);
            $key   = strtolower($key);
            $value = trim($value);
            switch ($key) {
                // cookie
                case 'cookie':
                    parse_str(str_replace('; ', '&', $value), $cookies);
                    break;
                // user-agent
                case 'user-agent':
                    $userAgent = $value;
                    break;
            }
        }
        
        return [
            'request_method'  => $requestMethod,
            'request_uri'     => $requestUri,
            'server_protocol' => $serverProtocol,
            'cookies'         => $cookies ?? [],
            'user-agent'      => $userAgent ?? '',
            'body'            => $httpBody
        ];
    }
}

if (!function_exists('build_http_protocol_res')) {
    function build_http_protocol_res(
        $body = ''
    ) {
        $headers = [];
        
        $headers[] = 'HTTP/1.1 200 OK';
        $headers[] = 'Content-Type: text/html;charset=utf-8';
        $headers[] = '';
        
        return sprintf('%s%s%s%s', implode(EOL, $headers), EOL, $body, EOL);
    }
}
