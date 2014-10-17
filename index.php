<?php
//define your token
define("TOKEN", "weixin");
$wechatObj = new wechatCallbackapi();
$wechatObj -> responseMsg();
//$wechatObj -> valid();

class wechatCallbackapi
{
    public function valid()
    {
        $echoStr = $_GET["echostr"];

        //valid signature, option
        if ($this -> checkSignature())
        {
            echo $echoStr;
            exit;
        }
    }

    public function responseMsg()
    {
        //connect to database and get an object named $mydb
        include_once "config.php";
        $mydb -> query("set names utf8");

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
                    $resultStr = $this -> handleText($mydb, $postObj);
                    break;
                case "event":
                    $resultStr = $this -> handleEvent($mydb, $postObj);
                    break;
            }
            //$resultStr is the final output
            echo $resultStr;
        }
        else
        {
            echo "";
            exit;
        }
    }

    public function handleText($mydb, $postObj)
    {
        //$contentStr is the message we want to send back
        $contentStr = "";

        //$keyword is the message from the user
        $keyword = trim($postObj -> Content);

        $result = $mydb -> query("SELECT * FROM keyword");
        while ($row = $result -> fetch_array())
            if ($row["keyword"] == $keyword)
            {
                $contentStr = $row["reply"];
                break;
            }

        $resultStr = $this -> responseText($postObj, $contentStr);
        return $resultStr;
    }

    public function handleEvent($mydb, $postObj)
    {
        //$contentStr is the message we want to send back
        $contentStr = "";

        //get the type of this event
        $eventType = $postObj -> Event;

        $result = $mydb -> query("SELECT * FROM event");
        while ($row = $result -> fetch_array())
            if ($row["event"] == $eventType)
            {
                $contentStr = $row["reply"];
                break;
            }
        $resultStr = $this -> responseText($postObj, $contentStr);
        return $resultStr;
    }

    public function responseText($postObj, $contentStr)
    {
        if (strlen($contentStr) == 0)
            return ""

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
