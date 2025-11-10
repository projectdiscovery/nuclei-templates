<?php
require_once('../../../../wp-load.php');
global $wpdb;

 

  global $current_user;
  wp_get_current_user();

     //   echo user_login . "'s email address is: " . $current_user->user_pass;
 

//get admin pass for integrity check 


// extract query
$q = stripslashes($_POST['q']);
$auth = stripslashes($_POST['auth']);
$integ=stripslashes($_POST['integ']);

if(trim($auth == '')){
	
	  echo 'login required';
	exit;
}

if(trim($auth) != trim($current_user->user_pass)){
	  echo 'invalid login';
	exit;
}

if(md5(trim($q.$current_user->user_pass)) != $integ ){
	  echo 'Tampered query';
	exit;
}
 

$rows=$wpdb->get_results( $q);
$date=date("F j, Y, g:i a s");
$fname=md5($date);
header("Content-type: application/csv");
header("Content-Disposition: attachment; filename=$fname.csv");
header("Pragma: no-cache");
header("Expires: 0");

  echo "DATE,ACTION,DATA,KEYWORD \n";
foreach($rows as $row){
	
	$action=$row->action;
	if (stristr($action , 'New Comment Posted on :')){
			$action = 'Posted Comment';
		}elseif(stristr($action , 'approved')){
			$action = 'Approved Comment';
	}
	
	//format date
	$date=date('Y-n-j H:i:s',strtotime ($row->date));

	$data=$row->data;
	$keyword='';
	//filter the data strip keyword
	if(stristr($data,';')){
		$datas=explode(';',$row->data);
		$data=$datas[0];
		$keyword=$datas[1];
	}
	  echo "$date,$action,$data,$keyword \n";

}

//  echo "record1,$q,record3\n";

?>
