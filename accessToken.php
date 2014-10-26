<?php
class accessToken
{
    private $APPID;
    private $APPSECRET;
    private $TOKEN_URL;
    private $tokenfile;

    public function __construct()
    {
        require_once 'appInfo.php';
        $this -> TOKEN_URL = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$this -> APPID."&secret=".$this -> APPSECRET;
    }

    public function access_token()
    {
        $refresh = true;
        $json = file_get_contents($this -> tokenfile);

        //if file exists and token has not expired, get token from json file
        if ($json)
        {
            $result = json_decode($json);
            if ($result -> errcode)
            {
                die($result -> errmsg);
            }
            $expires_in = $result -> expires_in;
            $time = time();
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
        $ACCESS_TOKEN = $result -> access_token;

        //save token and expiry time into a json file
        file_put_contents($this -> tokenfile, $json);

        return $ACCESS_TOKEN;
    }
}
?>
