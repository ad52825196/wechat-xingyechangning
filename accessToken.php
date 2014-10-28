<?php
class accessToken
{
    private $TOKEN_URL;
    private $tokenfile;

    public function __construct()
    {
        require_once 'appInfo.php';
        $this -> TOKEN_URL = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".APPID."&secret=".APPSECRET;
    }

    public function access_token()
    {
        $refresh = true;
        if (isset($_SERVER["HTTP_APPNAME"])) //SAE
        {
            $storage = new SaeStorage();
            $domain = "xycn";
            $json = $storage -> read($domain, $this -> tokenfile);
        }
        else //LOCAL
            $json = file_get_contents($this -> tokenfile);

        //if file exists and token has not expired, get token from json file
        if ($json)
        {
            $result = json_decode($json);
            $expires_in = $result -> expires_in;
            $time = time();
            if (isset($_SERVER["HTTP_APPNAME"])) //SAE
            {
                $storage = new SaeStorage();
                $domain = "xycn";
                $attrKey = $storage -> getAttr($domain, $this -> tokenfile, array("datetime"));
                $create_time = $attrKey["datetime"];
            }
            else //LOCAL
                $create_time = filemtime($this -> tokenfile);
            if ($time - $create_time < 0.9 * $expires_in)
            {
                $ACCESS_TOKEN = $result -> access_token;
                $refresh = false;
            }
        }

        if ($refresh)
            $ACCESS_TOKEN = $this -> get_access_token();

        return $ACCESS_TOKEN;
    }

    private function get_access_token()
    {
        $json = file_get_contents($this -> TOKEN_URL);
        $result = json_decode($json);
        if ($result -> errcode)
        {
            die($result -> errmsg);
        }
        $ACCESS_TOKEN = $result -> access_token;

        //save token and expiry time into a json file
        if (isset($_SERVER["HTTP_APPNAME"])) //SAE
        {
            $storage = new SaeStorage();
            $domain = "xycn";
            $storage -> write($domain, $this -> tokenfile, $json);
        }
        else //LOCAL
            file_put_contents($this -> tokenfile, $json);

        return $ACCESS_TOKEN;
    }
}
?>
