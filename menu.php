<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'accessToken.php';
$accessToken = new accessToken();
$ACCESS_TOKEN = $accessToken -> access_token();
$menu = new menu($ACCESS_TOKEN);
switch ($_GET["action"])
{
    case 'create':
        $menu -> create();
        break;
    case 'get':
        $menu -> get();
        break;
    case 'delete':
        $menu -> delete();
        break;
}

class menu
{
    private $menu;
    private $MENU_CREATE_URL;
    private $MENU_GET_URL;
    private $MENU_DELETE_URL;

    public function __construct($ACCESS_TOKEN)
    {
        require_once 'menuButton.php';
        $this -> MENU_CREATE_URL = "https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$ACCESS_TOKEN;
        $this -> MENU_GET_URL = "https://api.weixin.qq.com/cgi-bin/menu/get?access_token=".$ACCESS_TOKEN;
        $this -> MENU_DELETE_URL = "https://api.weixin.qq.com/cgi-bin/menu/delete?access_token=".$ACCESS_TOKEN;
    }

    public function create()
    {
        $ch = curl_init($this -> MENU_CREATE_URL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: '.strlen($this -> menu)));
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this -> menu);

        //This will output the returned json package to the browser
        curl_exec($ch);
        curl_close($ch);
    }

    public function get()
    {
        $ch = curl_init($this -> MENU_GET_URL);

        //This will output the returned json package to the browser
        curl_exec($ch);
        curl_close($ch);
    }

    public function delete()
    {
        $ch = curl_init($this -> MENU_DELETE_URL);

        //This will output the returned json package to the browser
        curl_exec($ch);
        curl_close($ch);
    }
}
?>
