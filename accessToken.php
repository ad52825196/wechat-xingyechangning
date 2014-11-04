<?php
class accessToken
{
    private $TOKEN_URL;
    private $tokenfile;
    private $kv;

    public function __construct()
    {
        require_once 'appInfo.php';
        $this -> TOKEN_URL = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".APPID."&secret=".APPSECRET;
        if (isset($_SERVER["HTTP_APPNAME"])) //SAE
        {
            $this -> kv = new SaeKV();
            $this -> kv -> init();
        }
    }

    public function access_token()
    {
        //get token if it exists
        if (isset($_SERVER["HTTP_APPNAME"])) //SAE
        {
            if ($ACCESS_TOKEN = $this -> kv -> get("access_token"))
            {
                $expires_in = $this -> kv -> get("expires_in");
                $create_time = $this -> kv -> get("create_time");
            }
        }
        else //LOCAL
        {
            if ($json = file_get_contents($this -> tokenfile))
            {
                $result = json_decode($json);
                $ACCESS_TOKEN = $result -> access_token;
                $expires_in = $result -> expires_in;
                $create_time = filemtime($this -> tokenfile);
            }
        }

        //token should not be expired
        $time = time();
        if (!$ACCESS_TOKEN || $time - $create_time > 0.9 * $expires_in)
            $ACCESS_TOKEN = $this -> get();

        return $ACCESS_TOKEN;
    }

    private function get()
    {
        $json = file_get_contents($this -> TOKEN_URL);
        $result = json_decode($json);
        if ($result -> errcode)
        {
            die($result -> errmsg);
        }
        $ACCESS_TOKEN = $result -> access_token;
        $expires_in = $result -> expires_in;

        //save token information
        if (isset($_SERVER["HTTP_APPNAME"])) //SAE
        {
            $this -> kv -> set("access_token", $ACCESS_TOKEN);
            $this -> kv -> set("expires_in", $expires_in);
            $this -> kv -> set("create_time", time());
        }
        else //LOCAL
            file_put_contents($this -> tokenfile, $json);

        return $ACCESS_TOKEN;
    }
}
?>
