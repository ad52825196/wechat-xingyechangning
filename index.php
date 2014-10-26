<?php
//define your token
define("TOKEN", "weixin");
$wechatObj = new wechatCallbackapi();
if (isset($_GET["echostr"]))
    $wechatObj -> valid();
else
    $wechatObj -> responseMsg();

class wechatCallbackapi
{
    private $mydb;

    public function __construct()
    {
        //connect to database and get an object named $mydb
        require_once "config.php";
    }

    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature, option
        if ($this -> checkSignature())
        {
            echo $echoStr;
            exit();
        }
    }

    public function responseMsg()
    {
        //get post data, may be due to the different environments
        $postStr = $GLOBALS["HTTP_RAW_POST_DATA"];

        //extract post data
        if (!empty($postStr))
        {
            /* libxml_disable_entity_loader is to prevent XML eXternal Entity Injection,
                the best way is to check the validity of xml by yourself */
            libxml_disable_entity_loader(true);
            $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $msgType = trim($postObj -> MsgType);
            $resultStr = "";

            switch ($msgType)
            {
                case "text":
                    $resultStr = $this -> handleText($postObj);
                    break;
                case "event":
                    $resultStr = $this -> handleEvent($postObj);
                    break;
            }
            //$resultStr is the final output
            echo $resultStr;
            exit();
        }
        else
        {
            echo "";
            exit();
        }
    }

    public function handleText($postObj)
    {
        //$contentStr is the message we want to send back
        $contentStr = "";

        //$keyword is the message from the user
        $keyword = trim($postObj -> Content);

        $sql = "SELECT reply FROM keyword WHERE keyword = '%s'";
        $sql = sprintf($sql, $keyword);
        $result = $this -> mydb -> query($sql);
        if ($row = $result -> fetch_assoc())
            $contentStr = $row["reply"];

        $resultStr = $this -> responseText($postObj, $contentStr);
        return $resultStr;
    }

    public function handleEvent($postObj)
    {
        //$contentStr is the message we want to send back
        $contentStr = "";

        //get the type and the key of this event
        $eventType = $postObj -> Event;
        $eventKey = $postObj -> EventKey;

        $sql = "SELECT reply FROM event WHERE event = '%s' and eventKey = '%s'";
        $sql = sprintf($sql, $eventType, $eventKey);
        $result = $this -> mydb -> query($sql);
        if ($row = $result -> fetch_assoc())
            $contentStr = $row["reply"];

        $resultStr = $this -> responseText($postObj, $contentStr);
        return $resultStr;
    }

    public function responseText($postObj, $contentStr)
    {
        if (strlen($contentStr) == 0)
            return "";

        $fromUsername = $postObj -> FromUserName;
        $toUsername = $postObj -> ToUserName;
        $time = time();
        $msgType = "text";
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    </xml>";
        $resultStr = sprintf($textTpl, $fromUsername, $toUsername, $time, $msgType, $contentStr);
        return $resultStr;
    }

    private function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];    

        $token = TOKEN;
        $tmpArr = array($token, $timestamp, $nonce);
        //use SORT_STRING rule
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode($tmpArr);
        $tmpStr = sha1($tmpStr);

        if ($tmpStr == $signature)
            return true;
        else
            return false;
    }
}
?>
