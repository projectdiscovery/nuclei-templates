<?php 
	class wp_automatic_youtube
	{
		public $url;
		public $id;
		public $ch;
		
		function __construct(){
			
			//curl ini
			$this->ch = curl_init();
			curl_setopt($this->ch, CURLOPT_HEADER,0);
			curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($this->ch, CURLOPT_TIMEOUT,20);
			curl_setopt($this->ch, CURLOPT_REFERER, 'http://www.bing.com/');
			curl_setopt($this->ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.9.0.8) Gecko/2009032609 Firefox/3.0.8');
			curl_setopt($this->ch, CURLOPT_MAXREDIRS, 5); // Good leeway for redirections.
			curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 1); // Many login forms redirect at least once.
			curl_setopt($this->ch, CURLOPT_COOKIEJAR , "cookie.txt");
			
		}
		
		public function url2id()
		{
			$aux = explode("?",$this->url);
			$aux2 = explode("&",$aux[1]);			
			foreach($aux2 as $campo => $valor)
			{
				$aux3 = explode("=",$valor);
				if($aux3[0] == 'v') $video = $aux3[1];
			}
			return $this->id = $video;
		}
		
		public function url2id_($url)
		{
			$aux = explode("?",$url);
			$aux2 = explode("&",$aux[1]);			
			foreach($aux2 as $campo => $valor)
			{
				$aux3 = explode("=",$valor);
				if($aux3[0] == 'v') $video = $aux3[1];
			}
			return $this->id = $video;
		}
		
		public function thumb_url($tamanho=NULL)
		{
			$tamanho = $tamanho == "maior"?"hq":"";				
			$this->url2id();
			return 'http://i1.ytimg.com/vi/'.$this->id.'/'.$tamanho.'default.jpg';
		}
		
		public function thumb($tamanho=NULL)
		{
			$tamanho = $tamanho == "maior"?"hq":"";
			$this->url2id();	
			return '<img class="youtube_thumb" src="http://i1.ytimg.com/vi/'.$this->id.'/'.$tamanho.'default.jpg">';			
		}
		
		public function info()
		{
			$feedURL = 'http://gdata.youtube.com/feeds/base/videos?q='.$this->id.'&client=ytapi-youtube-search&v=2';    
			$sxml = simplexml_load_file($feedURL);						
			foreach ($sxml->entry as $entry)
			{
				$details = $entry->content;	
				$info["titulo"] = $entry->title;
			}
			$details_notags = strip_tags($details);
			$texto = explode("From",$details_notags);
			$info["descricao"] = $texto[0];
			$aux = explode("Views:",$texto[1]);
			$aux2 = explode(" ",$aux[1]);
			$info["views"] = $aux2[0];
			
			$aux = explode("Time:",$texto[1]);
			$aux2 = explode("More",$aux[1]);
			$info["tempo"] = $aux2[0];
			
			$imgs = strip_tags($details,'<img>');
			$aux = explode("<img",$imgs);
			array_shift($aux);
			array_shift($aux);
			$aux2 = explode("gif\">",$aux[4]);
			array_pop($aux);
			$aux3 = $aux2[0].'gif">';
			$aux[] = $aux3;
			$imagens = '';
			foreach($aux as $campo => $valor)
			{
				$imagens .= '<img'.$valor;
			}
			$info["estrelas"] = $imagens;
			return $info;
		}
		
		public function search($palavra,$criteria)
		{
			$feedURL = 'http://gdata.youtube.com/feeds/base/videos?q='.$palavra.'&client=ytapi-youtube-search&v=2&'.$criteria;
			
			  echo $feedURL;
			exit;
			
   
			$info = array();
			
			//curl get
			$x='error';
			$url=$feedURL;
			curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
			curl_setopt($this->ch, CURLOPT_URL, trim($url));
			$exec=curl_exec($this->ch);
			$x=curl_error($this->ch);
			 
			
			$sxml = simplexml_load_string($exec);	
		
	
			$i=0;
			foreach ($sxml->entry as $entry)
			{
				
				
				if( stristr($entry->title, 'devicesupport') ) continue;
				
				
				$details = $entry->content;	
				$author  = $entry->author->name;
				  
				$info[$i]["title"] = $entry->title;	
				$info[$i] ['author'] = $author;
				$aux = explode($info[$i]["title"],$details);			
				$aux2 = explode("<a",$aux[0]);				
				$aux3 = explode('href="',$aux2[1]);
				$aux4 = explode('&',$aux3[1]);
				$info[$i]["link"] = $aux4[0];
				$details_notags = strip_tags($details);
				$texto = explode("From",$details_notags);
				$info[$i]["descreption"] = $texto[0];
				$aux = explode("Views:",$texto[1]);
				@$aux2 = explode(" ",$aux[1]);
				$info[$i]["views"] = $aux2[0];
				
				$aux = explode("Time:",$texto[1]);
				
				//geting duration
				$time_txt = $aux[1];
				$time_txt = trim( preg_replace('{More.*}s', '', $time_txt) );
				$info[$i]['duration'] = $time_txt;
				
				@$aux2 = explode("More",$aux[1]);
				$info[$i]["time"] = strtotime($entry->published);
				
				$imgs = strip_tags($details,'<img>');
				$aux = explode("<img",$imgs);
				array_shift($aux);
				array_shift($aux);
				$aux2 = explode("gif\">",$aux[4]);
				array_pop($aux);
				$aux3 = $aux2[0].'gif">';
				$aux[] = $aux3;
				$imagens = '';
				foreach($aux as $campo => $valor)
				{
					$imagens .= '<img'.$valor;
				}
				$info[$i]["rating"] = $imagens;
				$i++;
				
			 
				
				
			}
		 
			 
			return $info;
			
			
			
		}//search 
		
		//specific user or channel
		public function search2($user,$palavra,$criteria,$playlist = false)
		{
			$info = array();
		
			if($playlist){
				$feedURL = 'https://gdata.youtube.com/feeds/base/playlists/'.$user.'?'.$criteria;
			}else{
				$feedURL = 'http://gdata.youtube.com/feeds/base/users/'.$user.'/uploads?client=ytapi-youtube-search&v=2&'.$criteria;
			}
			
			if( trim(urldecode($palavra))  != '*' && $playlist == false){
				$feedURL = $feedURL.= '&q='.$palavra; 	
			}	  
			
			  echo '<br>'.$feedURL;
			 
			
			//curl get
			$x='error';
			$url=$feedURL;
			curl_setopt($this->ch, CURLOPT_HTTPGET, 1);
			curl_setopt($this->ch, CURLOPT_URL, trim($url));
			$exec=curl_exec($this->ch);
			$x=curl_error($this->ch);
			
				
			$sxml = simplexml_load_string($exec);
			 
			
			$i=0;
			foreach ($sxml->entry as $entry)
			{
				
				if( stristr($entry->title, 'devicesupport') ) continue;
				
				$details = $entry->content;
				$info[$i]["title"] = $entry->title;
				$aux = explode($info[$i]["title"],$details);
				$aux2 = explode("<a",$aux[0]);
				$aux3 = explode('href="',$aux2[1]);
				$aux4 = explode('&',$aux3[1]);
				$info[$i]["link"] = $aux4[0];
				$details_notags = strip_tags($details);
				$texto = explode("From",$details_notags);
				$info[$i]["descreption"] = $texto[0];
				$aux = explode("Views:",$texto[1]);
				@$aux2 = explode(" ",$aux[1]);
				$info[$i]["views"] = $aux2[0];
		
				$aux = explode("Time:",$texto[1]);
				
				//geting duration
				$time_txt = $aux[1];
				$time_txt = trim( preg_replace('{More.*}s', '', $time_txt) );
				$info[$i]['duration'] = $time_txt;
				
				@$aux2 = explode("More",$aux[1]);
				$info[$i]["time"] = strtotime($entry->published);
		
				$imgs = strip_tags($details,'<img>');
				$aux = explode("<img",$imgs);
				array_shift($aux);
				array_shift($aux);
				$aux2 = explode("gif\">",$aux[4]);
				array_pop($aux);
				$aux3 = $aux2[0].'gif">';
				$aux[] = $aux3;
				$imagens = '';
				foreach($aux as $campo => $valor)
				{
					$imagens .= '<img'.$valor;
				}
				$info[$i]["rating"] = $imagens;
				$i++;
			}
			return $info;
		}//search
		
		public function player($width,$height)
		{
			$this->url2id();
			if(trim($width) == '') $width=480;
			if(trim($height) == '') $height=385;
			//return  '<object width="'.$width.'" height="'.$height.'"><param name="movie" value="http://www.youtube.com/v/'.$this->id.'&fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/'.$this->id.'&fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" width="'.$width.'" height="'.$height.'"></embed></object>';
			return  '<iframe width="'.$width.'" height="'.$height.'" src="//www.youtube.com/embed/'.$this->id.'" frameborder="0" allowfullscreen></iframe>';
		}
	}
?>