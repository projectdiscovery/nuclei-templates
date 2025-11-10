<?php


if(! class_exists('Spintax')){

class Spintax {
	
 	//spin 
	function spin($html,$replace_similar = false){
		
 		
		for($i = 0 ;$i < 4 ; $i++){
			
			$html = $this->spintax_this($html,$replace_similar );
			
			if(! stristr($html, '{')){
				break;
			}
			
		}
		
		return $html;
		
	}

	//one level spin function
	function spintax_this($html,$replace_similar = false){
	
		preg_match_all('{\{([^{}]*?)\}}s', $html, $matches);
	
		$spintaxed_with_brackets = $matches[0];
		
		//print_r($spintaxed_with_brackets);
		 
		
		$spintaxed_without_brackets = $matches[1];
	
		$i = 0;
		foreach( $spintaxed_without_brackets as $set){
			
				//valid set let's explode to parts
				$parts = explode('|', $set);
				$random = rand(0,count($parts) -1);
				$random_part = $parts[$random];
				
				//echo "\n Replacing ".$spintaxed_with_brackets[$i] . " with ".$random_part;
				
				//replacing the set with the random part
				if($replace_similar ){
					$html = str_replace($spintaxed_with_brackets[$i],  $random_part , $html );
				}else{
					$html = $this->str_replace_first($spintaxed_with_brackets[$i],  $random_part , $html );
				}
	
			$i++;
		}
	
		return $html;
	
	}//one level spin

	// replace first match
	function str_replace_first($find, $replace, $subject){
		
		return implode($replace, explode($find, $subject, 2));
		
	}
	
}

}//class exists
?>