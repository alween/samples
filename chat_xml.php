<?php
require('class/xmlrpc.php');
require('class/db.cls.php');
//require('config/db_drbd.cnf.php');
require_once('/home/golive/public_html/class/db.cls.php');
$db = new DB_Connect(MYSQL_CHATBOX, true);
$db->log_type = MYSQL_CHATBOX;//MYSQL_CHATBOX = logs.intranet
require("instaconnect.php");

if(isset($HTTP_RAW_POST_DATA)) {
	$rxml = $HTTP_RAW_POST_DATA;
} else {
	$rxml = implode("\r\n", file('php://input'));
}

$rxml = urldecode($rxml);
$datex = date("Y-m-d H:i:s");
$db->logfile("chatbox initialize ".date("Y-m-d H:i:s"));
if(trim($rxml)<>'') {

	$rxml = str_replace('XMLDATA=', '', $rxml);
	$pxml = XML_unserialize($rxml);
			
	$partner = $pxml['GetSmsService']['Header']['Account'];
	$transid = $pxml['GetSmsService']['GetSmsList']['GetSms']['TransactionID'];
	$originator = $pxml['GetSmsService']['GetSmsList']['GetSms']['OriginatingNumber'];
	$datetime = $pxml['GetSmsService']['GetSmsList']['GetSms']['DateTime'];
	$body = $pxml['GetSmsService']['GetSmsList']['GetSms']['Body'];
	$csc = $pxml['GetSmsService']['GetSmsList']['GetSms']['Shortcode'];
	$carrier = $pxml['GetSmsService']['GetSmsList']['GetSms']['Carrier'];
	$context = $pxml['GetSmsService']['GetSmsList']['GetSms']['Context'];
	
	$dir = 'mo/';
	$fnp = $dir.'mo'.$transid.'_chat_'.date('YmdHis').'.txt'; 
	
	/*if($fp = @fopen($fnp, 'w+')) {		
		fwrite($fp, $rxml."\n");
		fclose($fp);
	}*/
	
	$tmp = split(" ", strtolower(trim($body)));
	$q0 = $tmp[0];
	$q1 = $tmp[1];
	
	$SRVC_TYPE = trim(strtoupper($context));
	$db->logfile("chatbox [$originator] [$SRVC_TYPE] $datex ");
	if ($SRVC_TYPE == 'CHAT') {
		$db->logfile("chatbox [$originator] [$SRVC_TYPE] [".MYSQL_CHATBOX."]");	
		$body = str_ireplace($SRVC_TYPE, '', $body);
		$body = trim($body);
		$db->logfile("chatbox [$originator] [$SRVC_TYPE] $body ");
		//$db = new Database($host, $user, $pass, $dbms);
		//$db = new DB_Connect(MYSQL_CHATBOX, true);
		$sqls = "SELECT * FROM chatbox.chatbox_question WHERE originator = '$originator' AND que = 1 ORDER BY id DESC LIMIT 1";
		$db->logfile("chatbox [$originator] [$SRVC_TYPE] $sqls ");		
		$rss = $db->query($sqls);
		
		$sessionid = NULL;
		$operatorid = NULL;
		
		if ($rss->num_rows > 0) {
			$rows = $rss->fetch_object();
			$sessionid = $rows->sessionid;
			$operatorid = $rows->operatorid;
			
			$sqlss = "SELECT * FROM chatbox.chatbox_transaction WHERE originator = '$originator' AND sessionid = '$sessionid' AND operatorid = '$operatorid' AND marked = 0";
			$db->logfile("chatbox [$originator] [$SRVC_TYPE] $sqlss ");		
			$rsss = $db->query($sqlss);
			
			if ($rsss->num_rows > 0) {				
				$que = 0;
 			} else {
				$sessionid = NULL;
				$operatorid = NULL;
				$que = 1; //$body .= $body . " : ) ";
			}
		} else $que = 1;
		
		$sql = "INSERT INTO chatbox.chatbox_question (originator, body, acctid, `datetime`, sessionid, operatorid, que, carrier, csc) VALUES ('$originator', '$body', '$partner', NOW(), '$sessionid', '$operatorid', $que, '$carrier', '$csc')";
		$db->logfile("chatbox [$originator] [$SRVC_TYPE] $sql ");		
		//mail("alween.delacruz@golivemobile.com", "Chat", "$uri ;; response=$sql ;;");
		$rs = $db->query($sql);
		$db->logfile("chatbox [$originator] [$SRVC_TYPE] insert id = ".$db->insert_id);				
		
	}	
//mail("alween.delacruz@golivemobile.com", "Chat", "$uri ;; response=$sql ;$SRVC_TYPE;$que;$rxml");
}
//mail("alween.delacruz@golivemobile.com", "Chat", "$uri ;; response=$sql ;$SRVC_TYPE;$que;$rxml");
header('HTTP/1.1 200 OK');
?>