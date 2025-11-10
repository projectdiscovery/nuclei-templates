<?php
/**
 * class:wpAutomaticDom to extract content from html using class, id, regex or xpath
 * @author ValvePress
 * @version:1.0.0
 */
 
class wpAutomaticDom {
	
	public $html;
	public $doc;
	public $debug;
	
	function __construct($html){
		 
		//make sure encoding exits
		if( stristr($html, '<meta http-equiv="Content-Type" content="text/html; charset') || stristr($html, '<meta http-equiv="content-type" content="text/html; charset')){
			//correct
		}else{
			
			//not correct find the charset
			preg_match_all('{charset=["|\']([^"]+?)["|\']}i', $html,$encMatches);
			$possibleCharSet = $encMatches[1];
			
			$possibleCharSet = isset($possibleCharSet[0]) ? $possibleCharSet[0] : '';
		 
			if(trim($possibleCharSet) == '') $possibleCharSet = 'UTF-8';
			
			$charSetMeta = '<meta http-equiv="content-type" content="text/html; charset=' . $possibleCharSet . '"/>';
			
			if(stristr($html, '<head>')){
				$html = str_replace('<head>', '<head>'.$charSetMeta, $html);
			}else{
				$html = str_replace('</head>', $charSetMeta . '/<head>', $html);
			}
			 
			 
			
		} 
		
		// Fix tables tbody
		preg_match_all('{(<table.*?>)([\s]*<.*?>)}s', $html ,$allTablesOpenMatches ) ;
		
		$allTablesOpenMatchesTwo = $allTablesOpenMatches[0];
		$allTablesOpenMatchesOne = $allTablesOpenMatches[1];
		$allTablesOpenMatchesAfter = $allTablesOpenMatches[2];
		$i=0;
		foreach ($allTablesOpenMatchesTwo as $allTablesOpenMatchesTwoSingle){
			
			if(! stristr($allTablesOpenMatchesTwoSingle, '<tbody') && ! stristr($allTablesOpenMatchesTwoSingle, '<thead')){
				//fix this
				$html = str_replace($allTablesOpenMatchesTwoSingle, $allTablesOpenMatchesOne[$i].'<tbody>'.$allTablesOpenMatchesAfter[$i], $html);
			} 
			
			$i++;
		}
		
		preg_match_all('{(<[^<]*?>[\s]*)(</table.*?>)}s', $html ,$allTablesCloseMatches ) ;
		$allTablesCloseMatchesBoth = $allTablesCloseMatches[0];
		$allTablesCloseMatchesPre = $allTablesCloseMatches[1];
		$allTablesCloseMatchesAfter = $allTablesCloseMatches[2];
		
		$i=0;
		foreach ( $allTablesCloseMatchesBoth as $allTablesCloseMatchesBothSingle ){
			
			if( ! stristr($allTablesCloseMatchesBothSingle, 'tbody') && ! stristr($allTablesCloseMatchesBothSingle, 'tfoot')){
				$html = str_replace($allTablesCloseMatchesBothSingle, $allTablesCloseMatchesPre[$i].'</tbody>'.$allTablesCloseMatchesAfter[$i], $html ) ;
			}
			
			$i++;
		}
		
		 		
		$this->html = $html;
		$this->doc  =  new DOMDocument;
		
		try {
			$internalErrors = libxml_use_internal_errors(true);
			@$this->doc->loadHTML($html);
			libxml_use_internal_errors($internalErrors);
			 
		} catch (Exception $e) {
			throw new Exception('Failed to load the Document as a Dom');
		}
		
	}
	
	/**
	 * Get content from the dom using an XPath
	 * @param string $xpath
	 * @return string[]
	 */
	function getContentByXPath($xpath,$inner=true){
		
		// xPath object
		$xpathObj = new DOMXPath($this->doc);
		$xpathMatches = @$xpathObj->query("$xpath");
 
		
		$allMatchs= array();
		
		if($xpathMatches == false) return $allMatchs;
		
		foreach ($xpathMatches as $element) {
		 	
			
		   $matchHtml = ''; // single match html
				
		   if($inner){

		   	$nodes = $element->childNodes;
			
			foreach ($nodes as $node) {
				
				$matchHtml.=  $this->doc->saveHTML($node). "\n";
			}
			
		   }else{
		   	 $matchHtml =  $this->doc->saveHTML($element);
		   }
			
			$allMatchs[] = $matchHtml;
			
		}
		

		
		return $allMatchs;
		
	}

	/**
	 * Get childrens by XPath
	 */
	function getChildsByXPath($xpath){
		
		// xPath object
		$xpathObj = new DOMXPath($this->doc);
		$xpathMatches = @$xpathObj->query("$xpath");
		
		$allMatchs= array();
		
		if($xpathMatches == false) return $allMatchs;
		
		foreach ($xpathMatches as $element) {
			
			
			$matchHtml = array(); // single match html
 				
				$nodes = $element->childNodes;
				
				foreach ($nodes as $node) {
					
					$matchHtml[] =  $this->doc->saveHTML($node) ;
				}
			 
			$allMatchs[] = $matchHtml;
			
		}
		
		return $allMatchs;
		
	}
	
	/**
	 * Get content from dom using class name
	 * @param string $className
	 * @return string[]
	 */
	function getContentByClass($className,$inner=true){
		 
		$className = trim($className) ;
		$XPath= '//*[contains(concat (" ", normalize-space(@class), " "), " '.$className.' ")]';
		return $this->getContentByXPath($XPath,$inner) ;
	}
	
	/**
	 * Get content from dom using id
	 * @param string $id
	 * @return string[]
	 */
	function getContentByID($id,$inner=true){
		$id=trim($id);
		$XPath = "//*[@id='$id']" ;
		return $this->getContentByXPath($XPath,$inner) ;
	}	
	
	/**
	 * Get default title from title tag or h1 tag 
	 * @return string the title
	 */
	function getTheTitle(){
		
		//return title from title tag
		preg_match('{<title>(.*?)</title>}s', $this->html,$titleMatchs);
		$possibleTitle = isset($titleMatchs[1]) ?  $titleMatchs[1] : '' ;
		
		if(trim($possibleTitle) != '' ) return trim($possibleTitle); 
		
		//get from h1
		preg_match('{<h1.*?>(.*?)</h1>}s', $this->html,$titleMatchs);
		$possibleTitle = $titleMatchs[1];
		if(trim($possibleTitle) != '' ) return trim($possibleTitle);
		
		//default empty
		return '';
		
	}
	

	function getFullContent(){
		
		//readability
		require_once 'wp_automatic_readability/wp_automatic_Readability.php';
		$wp_automatic_Readability = new wp_automatic_Readability ( $this->html );
		
		$wp_automatic_Readability->debug = false;
		$result = $wp_automatic_Readability->init ();
		
		if ($result) {
			
			// Redability Content
			$content = $wp_automatic_Readability->getContent ()->innerHTML;
			
			// Remove  wp_automatic_Readability attributes
			$content = preg_replace('{ wp_automatic_Readability\=".*?"}s', '', $content);
			
			// Fix iframe if exists
			preg_match_all('{<iframe[^<]*/>}s', $content,$ifrMatches);
			$iframesFound = $ifrMatches[0];
			
			foreach ($iframesFound as $iframeFound){
				
				$correctIframe  = str_replace('/>','></iframe>',$iframeFound);
				$content = str_replace($iframeFound, $correctIframe, $content);
				
			}
			
			// Cleaning redability for better memory
			unset($wp_automatic_Readability);
			unset($result);

 
			return $content;
			
		}else{
			echo '<br>Failed to find the content.';
			return '';
		}
		
	}
	
	/**
	 * Extract content by a regex ex <h1>(.*?)</h1>
	 * @param string $regex
	 */
	function getContentByRegex($regex){
		
		preg_match_all('{'.$regex.'}is', $this->html,$matchregex);
		  
		if(isset($matchregex[1])) return $matchregex[1];
		
		if(isset($matchregex[0])) return $matchregex[0];
 		
	}
	
	/**
	 * Find the link by xPath and all similar links in a page content. used by the multi-page scraper
	 * Similar links has the same XPath [no digets] and have the similar siblings
	 * @param string $xpath
	 * @return array all similar URLs
	 */
	function getSimilarLinks($xpath){
		
		
		//refine the xpath and find the a tag
		$xpath = trim($xpath);
		if(! stristr($xpath, '/a/') &&  ! preg_match('{/a$}',$xpath)  &&  ! stristr($xpath, '/a[') ){
			throw new Exception('Provided XPath does not contain the a tag');
		}else{
			
			//good we have the a tag if it is not the last one, make it the last
			if(!preg_match('{/a$}', $xpath) && ( stristr($xpath, '/a/') || stristr($xpath, '/a[')  ) ){
				
				if(!  stristr($xpath, '/a[')  ){
					$xpathParts = explode('/a/', $xpath);
					$lastPartIndex = count($xpathParts) - 1;
					unset($xpathParts[$lastPartIndex]);
					$xpath=implode('/a/',$xpathParts).'/a';
				}else{
					$xpath = preg_replace( '!(a\[\d*?\]).*!', "$1", $xpath);
				}
				
				
			}
			
			 
			//find the chosen node
			$xpathObj = new DOMXPath($this->doc);
			$xpathMatches = @$xpathObj->query("$xpath");
			 
			
			if(  isset($xpathMatches[0]) ) {
			 
			//well we have a match
			$choseNode = $xpathMatches[0];
			$chosenNodePath = ($choseNode->getNodePath());
		 
			}else{
				echo '<br>Failed to get the alleged node, trusting the provided XPath instead';
				$chosenNodePath = $xpath;
			}

			$chosenNodePathNoDigits = preg_replace('{\[\d*?\]}', '[]', $chosenNodePath);
			$chosenNodePathParts = explode('/', $chosenNodePath);
			
			
			echo '<br>Chosen node dom XPath:'.$chosenNodePath;
			
			if($this->debug) print_r($chosenNodePathParts);

			//get all links in the dom
			$LinksWithSimilarPath = array();
			$LinksWithSimilarPathTitles = array();
			$LinksWithSimilarPathStrict = array();
			
			
			$allLinksMatches =  @$xpathObj->query("//a");
			
			  
			foreach ($allLinksMatches as $singleLink){
				$currentNodePath = $singleLink->getNodePath() ;
			 
				$currentNodePathNoDigits = preg_replace('{\[\d*?\]}', '[]', $currentNodePath );
				if($currentNodePathNoDigits == $chosenNodePathNoDigits){
					
					$LinksWithSimilarPath[] = $singleLink->getAttribute('href');
					$LinksWithSimilarPathTitles[] = $singleLink->nodeValue;
					
					if($this->debug) echo "\n\n<br><br>".$singleLink->getNodePath().'<br><br>'.$singleLink->nodeValue . '<br><br>' ;
					
					//verify num of changes in xPath
					$numOfChanges = 0;
					$currentNodePathParts = explode('/', $currentNodePath);
					$nodeChangeIndex = 0 ; //where exactly there were a change
					$i = 0 ;
					
					foreach ($currentNodePathParts as $currentPart){
					 
						if($currentPart != $chosenNodePathParts[$i]){
							$nodeChangeIndex = $i;
							$numOfChanges++;
						} 
					
						$i++;
						
						
					}
					
					if($numOfChanges < 2 ){
						
						$LinksWithSimilarPathStrict[] = $singleLink->getAttribute('href');
						$LinksWithSimilarPathStrictTitles[] = $singleLink->nodeValue;
						$changeIndexArr[] = $nodeChangeIndex;
					} 
					
				
				}
			}
			
			if($this->debug){
				
				echo "\n<br>------- Strict similar XPath---------\n<br>";
				print_r($LinksWithSimilarPathStrict);
				print_r($changeIndexArr);
			}
			 
			
			if(count($LinksWithSimilarPathStrict) > 5){
				
				//better results are here lets find the odd result if any
				$values = array_count_values($changeIndexArr);
				arsort($values);
				
				if($this->debug){
					echo "\n<br>-------Most common change part index  ---------\n<br>";
					print_r($values);
				}
				
				//fix this line
				 $changeArrKeys = array_keys($values) ;
				 $correctNodeIndex = $changeArrKeys[0]; // index of the change 
				
				 echo "\n<br> All Links:" . count($allLinksMatches) . ", Similar links:".count($LinksWithSimilarPath) . " & Most similar:".count($LinksWithSimilarPathStrict); 
				 
				 if($this->debug)
				 echo "\n<br> Correct change XPath index:".$correctNodeIndex;
				 
				if(is_numeric($correctNodeIndex) ){
					foreach ($changeIndexArr as $changeKey => $changeValue){
						if($changeValue !=0 && $changeValue != $correctNodeIndex){
							unset($LinksWithSimilarPathStrict[$changeKey]);
							unset($LinksWithSimilarPathStrictTitles[$changeKey]);
						}
					}
				}
			  	
				return array($LinksWithSimilarPathStrict ,$LinksWithSimilarPathStrictTitles) ;
			}else{
				return array($LinksWithSimilarPath , $LinksWithSimilarPathTitles);
			}
 			
		}
	}
	
}