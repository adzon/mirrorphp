<?php
/**
 * Created by PhpStorm.
 * User: h
 * Date: 2020/3/19
 * Time: 1:35 AM
 */
error_reporting(0);

//注意，请将 "https://mirrorclient.test" 替换为你的镜像系统域名。
//伪静态规则可以直接使用 Wordpress、Laravel 的伪静态规则。
//Laravel Forge 可以直接安装，然后修改环境配置文件，内容为你的 Server 即可。

$server = "https://mirrorclient.test";

$mirror = new Mirror($server);

$mirror->visit();

Class Mirror
{

    public $host;
    public $uri;
    public $target;
    public $config;
    public $server;

    public function __construct($server)
    {
        $this->server = $server;
        $this->host = $_SERVER['HTTP_HOST'];
        $this->uri = $_SERVER['REQUEST_URI'];

        if(is_file('.env'))
            $this->server = trim(file_get_contents('.env'));

        $this->config();
        $this->check();
    }

    public function check()
    {
        $ip = self::getIP();
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            if ($_SERVER['REQUEST_URI'] == '/' || stripos($_SERVER['REQUEST_URI'], '/?source=') !== false) {
                try {
                    $url = $this->server . "/check?host=" . $_SERVER['HTTP_HOST'] . '&ip=' . $ip;

                    $headers = [
                        'User-Agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                        'Accept' => $_SERVER['HTTP_ACCEPT'] ?? '',
                        'Accept-Language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
                        'Referer' => $_SERVER['HTTP_REFERER'] ?? '',
                        'Client_IP' => $_SERVER['HTTP_CLIENT_IP'] ?? '',
                        'X-Forwarded-For' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
                        'Accept-Encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
                        'Accept-Charset' => $_SERVER['HTTP_ACCEPT_CHARSET'] ?? '',
                    ];
                    $response = $this->get($url, $headers);
                    print_r($response);

                    $content = $response['body'];
                    $array = json_decode($content, true);
                    print_r($array);
                    die();
                    if (isset($array['safe']) && $array['safe'] == 'true') {

                        if (!isset($array['lander_url']))
                            die("已经通过 Cloak 检测，但是你没有给站点绑定转化页，请检查。");

                        $content = file_get_contents($array['lander_url']);
                        $content = str_replace('[PID]', $array['pid'], $content);
                        $content = str_replace('[OFFER_ID]', $array['offer_id'], $content);
                        $content = str_replace('[OFFER_URL]', $array['offer_url'], $content);
                        $content = str_replace('[CLICK_ID]', $array['click_id'], $content);

                        header("Content-type:text/html;charset=utf-8");
                        die($content);
                    }

                } catch (\Exception $e) {
                     die($e->getMessage());
                }

            }
        }
    }

    public function visit()
    {
        if (!isset($this->config['target']))
            die("未配置，如果您已经添加站点，请等待5分钟。");

        $headers = [
            'User-Agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'Accept' => $_SERVER['HTTP_ACCEPT'] ?? '',
            'Accept-Language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'Referer' => $_SERVER['HTTP_REFERER'] ?? '',
        ];

        $response = $this->get($this->config['target'] . $this->uri, $headers, false, true);
        foreach ($response['headers'] as $key => $value) {
            header($key . ': ' . $value);
        }
        $content = str_replace($this->config['target'], $this->config['base_url'], $response['body']);
        die($content);
    }

    public function config()
    {
        if (!is_dir('.config'))
            mkdir('.config');
        $configFile = '.config' . '/' . md5($this->host) . '.l';
        clearstatcache();
        if (file_exists($configFile) && filemtime($configFile) > time() - 60 * 5) { // good to serve!
            $content = file_get_contents($configFile);
            $this->config = unserialize(gzuncompress($content));
            return;
        }
        $content = file_get_contents($this->server.'/config?host=' . $this->host);
        $array = json_decode($content, true);
        file_put_contents($configFile, gzcompress(serialize($array)));
        $this->config = $array;

        return;

    }

    public function get($url, $header = [], $gzip = false)
    {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //返回数据不直接输出
        if ($gzip)
            curl_setopt($ch, CURLOPT_ENCODING, "gzip"); //指定gzip压缩
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


