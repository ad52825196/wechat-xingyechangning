<?php
//define your token
define("TOKEN", "weixin");
$wechatObj = new Wechat();
if (isset($_GET["echostr"]))
    $wechatObj -> valid();
else
    $wechatObj -> responseMsg();

class Wechat
{
    private $mydb;
    //$postObj is the received data package
    private $postObj;

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
            $this -> postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
            $msgType = trim($this -> postObj -> MsgType);

            switch ($msgType)
            {
                case "text":
                    $resultStr = $this -> handleText();
                    break;
                case "event":
                    $resultStr = $this -> handleEvent();
                    break;
            }
        }

        //$resultStr is the final output
        echo $resultStr;
        exit();
    }

    private function handleText()
    {
        //$content is the message from the user
        //$this -> postObj -> Content is created by user, so trim() method needs to be applied
        $content = trim($this -> postObj -> Content);
        $search = array("a ", "an ", "the ", "的");
        $content = str_ireplace($search, "", $content);

        $sql = "SELECT keyword FROM reply WHERE event = 'text' ORDER BY Length(keyword) DESC";
        if ($result = $this -> mydb -> query($sql))
        {
            while ($row = $result -> fetch_assoc())
            {
                $pos = strpos($content, $row["keyword"]);
                //strpos() function could return 0 which may also be recognised as false, so !== needs to be applied
                if ($pos !== false)
                {
                    $keyword = $row["keyword"];
                    break;
                }
            }
            //if $row["keyword"] found in $content
            if ($keyword)
            {
                $sql = "SELECT * FROM reply WHERE event = 'text' AND keyword = '%s' ORDER BY no";
                $sql = sprintf($sql, $keyword);

                return $this -> response($sql);
            }
        }

        return $resultStr;
    }

    private function handleEvent()
    {
        //get the type and the key of this event
        $event = $this -> postObj -> Event;
        $keyword = $this -> postObj -> EventKey;

        $sql = "SELECT * FROM reply WHERE event = '%s' AND keyword = '%s' ORDER BY no";
        $sql = sprintf($sql, $event, $keyword);

        return $this -> response($sql);
    }

    private function response($sql)
    {
        if ($result = $this -> mydb -> query($sql))
        {
            $row = $result -> fetch_assoc();
            $msgType = $row["msgType"];
            switch ($msgType)
            {
                case "text":
                    //$contentStr is the text message we want to send back
                    $contentStr = $row["content"];
                    $resultStr = $this -> responseText($contentStr);
                    break;
                case "news":
                    $itemTpl = "<item>
                                <Title><![CDATA[%s]]></Title>
                                <Description><![CDATA[%s]]></Description>
                                <PicUrl><![CDATA[%s]]></PicUrl>
                                <Url><![CDATA[%s]]></Url>
                                </item>";
                    $no = 0;
                    do
                    {
                        $title = $row["title"];
                        $description = $row["description"];
                        $picurl = $row["picurl"];
                        $url = $row["url"];
                        if (strlen($title.$description.$picurl.$url) > 0)
                        {
                            //$itemStr consists of all the information of an article
                            $itemStr = sprintf($itemTpl, $title, $description, $picurl, $url);
                            //$articleStr consists of all the articles
                            $articleStr .= $itemStr;
                            $no++;
                        }
                    } while ($row = $result -> fetch_assoc());
                    $resultStr = $this -> responseNews($articleStr, $no);
                    break;
            }
            $result -> free();
            $this -> mydb -> close();
        }

        return $resultStr;
    }

    private function responseText($contentStr)
    {
        if (strlen($contentStr) == 0)
            return "";

        $fromUsername = $this -> postObj -> ToUserName;
        $toUsername = $this -> postObj -> FromUserName;
        $time = time();
        $msgType = "text";
        $textTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <Content><![CDATA[%s]]></Content>
                    </xml>";
        $resultStr = sprintf($textTpl, $toUsername, $fromUsername, $time, $msgType, $contentStr);
        return $resultStr;
    }

    private function responseNews($articleStr, $no)
    {
        if ($no == 0)
            return "";

        $fromUsername = $this -> postObj -> ToUserName;
        $toUsername = $this -> postObj -> FromUserName;
        $time = time();
        $msgType = "news";
        $newsTpl = "<xml>
                    <ToUserName><![CDATA[%s]]></ToUserName>
                    <FromUserName><![CDATA[%s]]></FromUserName>
                    <CreateTime>%s</CreateTime>
                    <MsgType><![CDATA[%s]]></MsgType>
                    <ArticleCount>%d</ArticleCount>
                    <Articles>%s</Articles>
                    </xml>";
        $resultStr = sprintf($newsTpl, $toUsername, $fromUsername, $time, $msgType, $no, $articleStr);
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
