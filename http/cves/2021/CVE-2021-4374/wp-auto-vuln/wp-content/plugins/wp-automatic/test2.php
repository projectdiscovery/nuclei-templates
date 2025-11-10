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

 //curl get
 $x='error';
 $url='http://jobviewtrack.com/en-us/job-191f416b5808034e7400410003412b0902491a0d0c01286d484141443c1a46174b612d0f090e0b45111a6b355f47512c1a52575b/09a26146b4d0697af724fccd1b916578.html?affid=a551ffb1b11a967e629c447bc929c067';
 curl_setopt($ch, CURLOPT_HTTPGET, 1);
 curl_setopt($ch, CURLOPT_URL, trim($url));
 $exec=curl_exec($ch);
 $x=curl_error($ch);
 
 $cuinfo = curl_getinfo($ch);

 print_r( $cuinfo);
 
 echo $exec.$x;

exit;

 $exec = file_get_contents('test.txt');
 
 $json = json_decode($exec);

 echo '<pre>';
 print_r($json);
 exit;
 echo $exec;
 
 
 ?>