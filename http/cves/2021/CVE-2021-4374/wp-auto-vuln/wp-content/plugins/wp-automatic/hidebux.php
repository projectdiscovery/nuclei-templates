<?php

//curl ini
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER,0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT,20);
curl_setopt($ch, CURLOPT_REFERER, 'http://www.bing.com/');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
curl_setopt($ch, CURLOPT_COOKIEJAR , "cookie.txt");

$keyword = 'manga';
$page=0;
 
$keywordenc = urlencode ( $keyword  );

$curlurl="http://www.hidebux.com/service/includes/process.php?action=update";
$curlpost="u=https%3A%2F%2Fwww.google.co.uk%2Fsearch%3Fnum%3D100%26sclient%3Dpsy-ab%26site%3D%26source%3Dhp%26btnG%3DSearch%26q%3Dsite%253Aezinearticles.com%2B{$keywordenc}%26start%3D$page&allowCookies=on";
 
//$curlpost="u=https%3A%2F%2Fwww.google.co.uk%2Fsearch%3Fq%3Dgas%26gws_rd%3Dssl&encodeURL=on&allowCookies=on";

// Get the search page

//curl post
curl_setopt($ch, CURLOPT_URL, $curlurl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $curlpost);
curl_setopt($ch, CURLOPT_HEADER,1);
$exec=curl_exec($ch);
curl_setopt($ch, CURLOPT_HEADER,0);

  echo $exec;
