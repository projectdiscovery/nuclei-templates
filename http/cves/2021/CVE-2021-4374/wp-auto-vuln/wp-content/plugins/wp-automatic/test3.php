<?php 
 
//curl ini
$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER,0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT,60);
curl_setopt($ch, CURLOPT_REFERER, 'http://www.bing.com/');
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
curl_setopt($ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
curl_setopt($ch, CURLOPT_COOKIEJAR , "cookie.txt");

//curl post run-sync
$curlurl="https://api.apify.com/v2/acts/apify~web-scraper/runs?token=B7nfMAJRXPDe9MBmzeDARyezL";
$curlurl="https://api.apify.com/v2/acts/apify~web-scraper/run-sync-get-dataset-items?token=B7nfMAJRXPDe9MBmzeDARyezL";
$curlpost="parameters"; // q=urlencode(data)
curl_setopt($ch, CURLOPT_URL, $curlurl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, file_get_contents('test.json')); 
curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: application/json") );
$x='error';
$exec=curl_exec($ch);
$x=curl_error($ch);

$json = json_decode($exec);

print_r($json);

echo $x;

 
?>