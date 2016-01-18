<?php
set_time_limit(0);

require('class/xmlrpc.php');
require("instaconnect.php");
include('class/api.php');
require_once('/home/golive/public_html/class/db.cls.php');
$db = new DB_Connect(MYSQL_INSTACONNECT, true);

$db->log_type = MYSQL_INSTACONNECT;

if(isset($HTTP_RAW_POST_DATA)) {
	$rxml = $HTTP_RAW_POST_DATA;
} else {
	$rxml = implode("\r\n", file('php://input'));
}
#192.168.0.1
#Gol1v3inc5002012

$host = '50.17.202.43';
$username = 'asteriskvoip';
$password = 'eXes45mIgs&fohn';
$dbms = 'asteriskvoip';

/*mysql_connect($host,$username,$password) or die ('Error connecting to mysql');
mysql_select_db($dbms); */

$db_voip = new mysqli($host, $username, $password, $dbms);	

#$dbserver = "10.10.26.134";
$dbserver = "cluster-db.crfsqbh1kkpg.us-west-2.rds.amazonaws.com"; 
$dbuser = "voicechange_user";
$dbpass = "qYdY2U44wWo331793Mff";
$dbdata = "voicechanger_web";

$db2 = new mysqli($dbserver,$dbuser,$dbpass,$dbdata);


$rxml = urldecode($rxml);
$db->logfile("voicechanger; $rxml ;");

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
   
   # SAMPLE DATA #
	#$csc = 56474;
	#$originator = "16503916605";
	#$body = "VC 16503916606";

	$receiver = trim(preg_replace('/[^0-9]/', "",$body));
	
	
	if ( is_numeric($receiver) && (strlen((string)$receiver) == 11 || strlen((string)$receiver) == 10) ) {
		if ( strlen((string)$receiver) == 11 ) {
			$receiver = substr($receiver,1);
		}
		
		$orig = $originator;
		if ( strlen((string)$originator) == 11 ) {
			$originator = substr($originator,1);
		}
		
		
		$sel = "SELECT id FROM voicechanger_web.personal_info WHERE phone LIKE '%{$originator}%';";
		$result = $db2->query($sel);
		$id = $result->num_rows;
		$db2->close();
				
		$db->logfile("voicechanger; $originator ; count: $id; sql : $sel");
		
		$originator = $orig;
		
		$sql_subs = "select keyword from `$csc`.`subscribers` where `msisdn` = '$originator' limit 1";
		$rs_subs = $db->query($sql_subs);
		$nr_subs = $rs_subs->num_rows;
		$db->logfile("voicechanger; $originator ; $body; $carrier  ; $nr_subs ; $sql_subs");
		
		$selectResId = "SELECT resellerid FROM CGS.trunk_master WHERE csc = $csc;";
		$rs = $db->query($selectResId);
		#if($nr_subs > 0 && $id > 0 ){ # <--- REMOVED PROFILE CHECKING IF ACTIVE
		if($nr_subs > 0 ){
			$row_subs = $rs_subs->fetch_assoc();
			$rs_sub = $rs->fetch_assoc();
			$resellerid = ($rs_sub["resellerid"] != "") ? $rs_sub["resellerid"] : 10130;
			$keyword = $row_subs['keyword'];
		
			$sql_EXP = "Insert into `asteriskvoip`.`vc` (caller, receiver, datetime, path, tag , carrier, keyword, resellerid) values ('".$originator."','".$receiver."',now(),'',0,'$carrier','$keyword','$resellerid') ";
			$rsEXP = $db_voip->query($sql_EXP) or print($sql_EXP);
			$db->logfile("voicechanger ; $body [$originator] Insert-> $sql_EXP\n");
			#echo "$sql_EXP"; exit;
		
		} else {
			$db->logfile("voicechanger sending not registered MT; originator -  $originator");
			$fmsg = "Please join the campaign in order to join voicechanger!";
			$fmsg = "Your voicechanger. Register at voicechangerplus.com to 100 downloads monthly";
			$o1 = new GlmIT_API();
			if ($carrier <> '31003' || $carrier <> '77' || $carrier <> '31002' || $carrier <> '383')
			{
			$_data = $o1->api_method('Pincode', 'Send_SMS',
					array('csc' => $csc,
							'carrier' => $carrier,
							'msisdn' => $originator,
							'body' => $fmsg,
							'acctid' => $partner,
							'keyword' => NULL,
							'offline' => NULL,
							'name' => NULL));
			$db->logfile("voicechanger NOT a subscriber [$originator] : " . json_encode($_data));
			}
			exit;
		}
		
		echo "echo $originator ;; $body ".$nr_subs . "<br>$sql_subs";
		exit;
	} else {
		$fmsg = "VoiceChangerPlus: Send Correct 10 digits Phone Number You'd like to call.";
		$o1 = new GlmIT_API();
		$_data = $o1->api_method('Pincode', 'Send_SMS',
				array('csc' => $csc,
						'carrier' => $carrier,
						'msisdn' => $originator,
						'body' => $fmsg,
						'acctid' => $partner,
						'keyword' => NULL,
						'offline' => NULL,
						'name' => NULL));
		$db->logfile("voicechanger NOT a subscriber [$originator]");
	}
	
	
	
 
} 

echo "DONE";
?>