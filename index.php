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
        }
        else
        {
            echo "";
            exit;
        }
    }

    public function handleText($postObj)
    {
        //$contentStr is the message we want to send back
        $contentStr = "";

        //$keyword is the message from the user
        $keyword = trim($postObj -> Content);

        switch ($keyword)
        {
            case "Testing":
                $contentStr = "Pass!";
                break;
        }
        $resultStr = $this -> responseText($postObj, $contentStr);
        return $resultStr;
    }

    public function handleEvent($postObj)
    {
        //$contentStr is the message we want to send back
        $contentStr = "";

        //get the type of this event
        switch ($postObj -> Event)
        {
            case "subscribe":
                $contentStr = "感谢您关注兴业长宁微信服务号！";
                break;
        }
        $resultStr = $this -> responseText($postObj, $contentStr);
        return $resultStr;
    }

    public function responseText($postObj, $contentStr)
    {
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
