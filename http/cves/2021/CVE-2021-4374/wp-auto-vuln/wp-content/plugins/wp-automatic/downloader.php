<?php
 
function curl_exec_follow( &$ch){

	$max_redir = 3;

	for ($i=0;$i<$max_redir;$i++){

		$exec=curl_exec($ch);
		$info = curl_getinfo($ch);

		if($info['http_code'] == 301 ||  $info['http_code'] == 302){
				
			curl_setopt($ch, CURLOPT_URL, trim($info['redirect_url']));
			$exec=curl_exec($ch);
				
		}else{
				
			//no redirect just return
			break;
				
		}


	}

	return $exec;

}

$link=$_GET['link'];//urldecode();
    $link=str_replace('httpz','http',$link);
    //$link='http://ointmentdirectory.info/%E0%B8%81%E0%B8%B2%E0%B8%A3%E0%B9%81%E0%B8%AA%E0%B8%94%E0%B8%87%E0%B8%A0%E0%B8%B2%E0%B8%9E%E0%B8%99%E0%B8%B4%E0%B9%88%E0%B8%87-%E0%B8%97%E0%B8%AD%E0%B8%94%E0%B8%9B%E0%B8%A5%E0%B8%B2%E0%B9%80%E0%B8%9E';
    //  echo $link ;
    //exit ;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, trim($link));
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
    curl_setopt($ch,CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_REFERER, 'http://bing.com');
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
    curl_setopt($ch,CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
    curl_setopt($ch,CURLOPT_FOLLOWLOCATION, 0); // Many login forms redirect at least once.
    
    $exec=curl_exec_follow($ch);

    
    $res=array();
    //get the link 
    $curlinfo=curl_getinfo($ch);
    
     
	$original_link=$curlinfo['url'];
	$original_link=str_replace("?hop=zzzzz",'',$original_link);
	$res['link']=$original_link;
	
	//get the title
	preg_match("/<title>(.*?)<\/title>/i",$exec,$matches );
	$ret=$matches[1];

	$res['title']=$ret;
	$res['status']='success';

	$ret = array();
	
	/*** a new dom object ***/
	$dom = new domDocument;
	
	/*** get the HTML (suppress errors) ***/
	@$dom->loadHTML($exec);
	
	/*** remove silly white space ***/
	$dom->preserveWhiteSpace = false;
	
	/*** get the links from the HTML ***/
	$text = $dom->getElementsByTagName('p');
	
	/*** loop over the links ***/
	foreach ($text as $tag)
	{
		$textContent = $tag->textContent;
	
		if(trim($textContent) == '' || strlen($textContent) < 25 || stristr($textContent, 'HTTP') || stristr($textContent, '$')) continue;
		$ret[] = $textContent;
		
	}
	
	$res['text']=$ret;
	
	  echo json_encode($res);

	exit;
    
    @unlink('files/temp.html');
    $cont=curl_exec($ch);
    //$cont=file_get_contents($link);
    if (curl_error($ch)){
    	  echo 'Curl Error:error:'.curl_error($ch);
    }
    file_put_contents('files/temp.html',$link.$cont);
?>