<?php

// Globals
global $post;
global $wpdb;
global $camp_general;
global $post_id;
global $camp_options;
global $post_types;

global $camp_post_category;

global $camp_translate_from;
global $camp_translate_to;
global $camp_translate_to_2;

?>

<div class="TTWForm-container" dir="ltr">
	<div class="TTWForm">
		<div class="panes">
		
			 <div id="field1zz-container" class="field f_100">
               <div class="option clearfix">
                    <input data-controls="wp_automatic_spin_title" name="camp_options[]" id="field2-1" value="OPT_TBS" type="checkbox">
                    <span class="option-title">
							Spin Posted Content using "the best spinner" <i>(require <a target="_blank" href="https://paykstrt.com/10313/57123">the best spinner</a> account)</i> 
                    </span>
                    <br>
               </div>
               
               <div id="wp_automatic_spin_title"  class="field f_100">
               
	               	<div class="option clearfix">
	                    <input name="camp_options[]" id="field2-1" value="OPT_TBS_TTL" type="checkbox">
	                    <span class="option-title">
								Don't spin the title 
	                    </span>
	                    <br>
	               </div>
               
               </div>
               
		 	</div>
		
		
			<div id="translate_post" class="field f_100">
               <div class="option clearfix">
                    
                    <?php 
                    
	                    
	                    // Microsoft langues and langauges codes 
	                    $langs = explode(',',"---,Arabic,Bosnian (Latin),Bulgarian,Catalan,Chinese Simplified,Chinese Traditional,Croatian,Czech,Danish,Dutch,English,Estonian,Finnish,French,German,Greek,Haitian Creole,Hebrew,Hindi,Hmong Daw,Hungarian,Indonesian,Italian,Japanese,Kiswahili,Klingon,Klingon (pIqaD),Korean,Latvian,Lithuanian,Malay,Maltese,Norwegian,Persian,Polish,Portuguese,QuerÃ©taro Otomi,Romanian,Russian,Serbian (Cyrillic),Serbian (Latin),Slovak,Slovenian,Spanish,Swedish,Thai,Turkish,Ukrainian,Urdu,Vietnamese,Welsh,Yucatec Maya" );
	                    $langs_c = explode(',', "no,ar,bs-Latn,bg,ca,zh-CHS,zh-CHT,hr,cs,da,nl,en,et,fi,fr,de,el,ht,he,hi,mww,hu,id,it,ja,sw,tlh,tlh-Qaak,ko,lv,lt,ms,mt,nor,fa,pl,pt,otq,ro,ru,sr-Cyrl,sr-Latn,sk,sl,es,sv,th,tr,uk,ur,vi,cy,yua");
	                    
	                    // Google languages and languages codes
	                    $g_langs=array("---","Auto-Detect","Afrikaans","Albanian","Arabic","Armenian","Belarusian","Bulgarian","Catalan","Chinese Simplified","Chinese Traditional","Croatian","Czech","Danish","Dutch","English","Estonian","Filipino","Finnish","French","Galician","German","Greek","Hebrew","Hindi","Hungarian","Icelandic","Indonesian","Irish","Italian","Japanese","Korean","Latvian","Lithuanian","Macedonian","Malay","Maltese","Persian","Polish","Portuguese","Romanian","Russian","Serbian","Slovak","Slovenian","Spanish","Swahili","Swedish","Thai","Turkish","Ukrainian","Vietnamese","Welsh","Yiddish","Norwegian");
	                    $g_langs_c=array("no","auto","af","sq","ar","hy","be","bg","ca","zh-CN","zh-TW","hr","cs","da","nl","en","et","tl","fi","fr","gl","de","el","iw","hi","hu","is","id","ga","it","ja","ko","lv","lt","mk","ms","mt","fa","pl","pt","ro","ru","sr","sk","sl","es","sw","sv","th","tr","uk","vi","cy","yi","nor");
	                    
	                    $g_langs=array("---","Auto-Detect","Afrikaans","Albanian","Amharic","Arabic","Armenian","Azerbaijani","Basque","Belarusian","Bengali","Bosnian","Bulgarian","Catalan","Cebuano","Chichewa","Chinese Simplified","Chinese Traditional","Corsican","Croatian","Czech","Danish","Dutch","English","Esperanto","Estonian","Filipino","Finnish","French","Frisian","Galician","Georgian","German","Greek","Gujarati","Haitian Creole","Hausa","Hawaiian","Hebrew","Hindi","Hmong","Hungarian","Icelandic","Igbo","Indonesian","Irish","Italian","Japanese","Javanese","Kannada","Kazakh","Khmer","Korean","Kurdish (Kurmanji)","Kyrgyz","Lao","Latin","Latvian","Lithuanian","Luxembourgish","Macedonian","Malagasy","Malay","Malayalam","Maltese","Maori","Marathi","Mongolian","Myanmar (Burmese)","Nepali","Norwegian","Pashto","Persian","Polish","Portuguese","Punjabi","Romanian","Russian","Samoan","Scots Gaelic","Serbian","Sesotho","Shona","Sindhi","Sinhala","Slovak","Slovenian","Somali","Spanish","Sundanese","Swahili","Swedish","Tajik","Tamil","Telugu","Thai","Turkish","Ukrainian","Urdu","Uzbek","Vietnamese","Welsh","Xhosa","Yiddish","Yoruba","Zulu");
	                    $g_langs_c=array("no","auto","af","sq","am","ar","hy","az","eu","be","bn","bs","bg","ca","ceb","ny","zh-CN","zh-TW","co","hr","cs","da","nl","en","eo","et","tl","fi","fr","fy","gl","ka","de","el","gu","ht","ha","haw","iw","hi","hmn","hu","is","ig","id","ga","it","ja","jw","kn","kk","km","ko","ku","ky","lo","la","lv","lt","lb","mk","mg","ms","ml","mt","mi","mr","mn","my","ne","nor","ps","fa","pl","pt","pa","ro","ru","sm","gd","sr","st","sn","sd","si","sk","sl","so","es","su","sw","sv","tg","ta","te","th","tr","uk","ur","uz","vi","cy","xh","yi","yo","zu");

	                    //yandex languages
	                    $y_langs = array( "---" ,  "Auto Detect" , "Azerbaijan","Malayalam","Albanian","Maltese","Amharic","Macedonian","English","Maori","Arabic","Marathi","Armenian","Mari","Afrikaans","Mongolian","Basque","German","Bashkir","Nepali","Belarusian","Bengali","Punjabi","Burmese","Papiamento","Bulgarian","Persian","Bosnian","Polish","Welsh","Portuguese","Hungarian","Romanian","Vietnamese","Russian","Haitian (Creole)","Cebuano","Galician","Serbian","Dutch","Sinhala","Hill Mari","Slovakian","Greek","Slovenian","Georgian","Swahili","Gujarati","Sundanese","Danish","Tajik","Hebrew","Thai","Yiddish","Tagalog","Indonesian","Tamil","Irish","Tatar","Italian","Telugu","Icelandic","Turkish","Spanish","Udmurt","Kazakh","Uzbek","Kannada","Ukrainian","Catalan","Urdu","Kyrgyz","Finnish","Chinese","French","Korean","Hindi","Xhosa","Croatian","Khmer","Czech","Laotian","Swedish","Latin","Scottish","Latvian","Estonian","Lithuanian","Esperanto","Luxembourgish","Javanese","Malagasy","Japanese","Malay" );
	                    $y_langs_c = array(  "no" , "auto" , "az","ml","sq","mt","am","mk","en","mi","ar	","mr","hy","mhr","af","mn","eu","de","ba","ne","be	","bn","pa","my","pap","bg","fa","bs","pl","cy","pt","hu","ro","vi","ru","ht","ceb","gl","sr","nl","si","mrj","sk","el","sl","ka","sw","gu","su","da","tg","he","th","yi","tl","id","ta","ga","tt","it","te","is","tr","es","udm","kk","uz","kn","uk","ca","ur","ky","fi","zh","fr","ko","hi","xh","hr","km","cs","lo","sv","la","gd","lv","et","lt","eo","lb","jv","mg","ja","ms" );
	                    
	                    //Deepl
	                    $d_langs = array( "---" ,"Auto-Detect" , "English","German","French","Spanish","Portuguese","Italian","Dutch","Polish","Russian" , "Japanese" , "Chinese");
	                    $d_langs_c= array('no', 'auto' , "EN","DE","FR","ES","PT","IT","NL","PL","RU" , "JA" , "ZH" );
	                    
                    ?>
                    
                    <input name="camp_options[]" id="translate_option" value="OPT_TRANSLATE" type="checkbox">
                    <span class="option-title">
							Translate the post before posting 
        				</span>
                    <br>
                    
                    
		            <div id="translate_c" class="field f_100">
		            
		            
		            <div id="field1zz-container" class="field f_100">
			               <label>
			                    Translator:
			               </label>
			               <select name="cg_translate_method"  data-filters=".wp_automatic_lang_select" >
			                    <option  value="googleTranslator"  <?php @wp_automatic_opt_selected( 'googleTranslator' , $camp_general['cg_translate_method'] ) ?>  >
			                         Google Translator
			                    </option>
			                    <option value="microsoftTranslator"  <?php @wp_automatic_opt_selected('microsoftTranslator',$camp_general['cg_translate_method']) ?> >
			                         Microsoft Translator API
			                    </option>
			                   
			                    <option value="yandexTranslator"  <?php @wp_automatic_opt_selected('yandexTranslator',$camp_general['cg_translate_method']) ?> >
			                         Yandex Translator API
			                    </option>
			                    
			                    <option value="deeplTranslator"  <?php @wp_automatic_opt_selected('deeplTranslator',$camp_general['cg_translate_method']) ?> >
			                         Deepl Pro API Translator
			                    </option>  
			                    
			                    
			               </select>
			          </div>
		            
		               From  
		                
		               	<select name="camp_translate_from" class="wp_automatic_lang_select translate_t" style="width:25%;padding:0;">
		               		 
		               		 <?php
							 
		               		 // Microsoft Languages output.
		               		 $i=0; 
		               		 
		               		 foreach($langs as $lang){
		               		 	?>
		               		 	  
		               		 	  <option data-filter-val="microsoftTranslator"   value="<?php   echo $langs_c[$i] ?>"  
		               		 	  <?php 
		               		 	  
		               		 	  if( $camp_general['cg_translate_method'] == 'microsoftTranslator')
		               		 	  { 
		               		 	  	@wp_automatic_opt_selected($langs_c[$i],$camp_translate_from); 
		               		 	  } 
		               		 	  
		               		 	  ?> ><?php   echo $langs[$i]?></option>
		               		 		 
		               		 	<?php
		               		 	
								$i++;
		               		 }
		               		 
		               		 // Google Languages output.
		               		 $i=0;
		               		 foreach($g_langs as $lang){
		               		 	?>
		               		 		               		 	  
               		 		        <option data-filter-val="googleTranslator"   value="<?php   echo $g_langs_c[$i] ?>"  
               		 		        <?php 
               		 		        
               		 		        if( $camp_general['cg_translate_method'] == 'googleTranslator') {
               		 		        	@wp_automatic_opt_selected($g_langs_c[$i],$camp_translate_from);
               		 		        } ?> ><?php   echo $g_langs[$i]?></option>
               		 		               		 		 
               		 		       <?php
               		 		               		 	
               		 			   $i++;
               		 		  }
               		 		  
               		 		  //Yandex languages 
               		 		  $i=0;
               		 		  foreach($y_langs as $lang){
               		 		  	?>
		               		 		               		 	  
               		 		        <option data-filter-val="yandexTranslator"   value="<?php   echo $y_langs_c[$i] ?>"  
               		 		        <?php 
               		 		        
               		 		        if( $camp_general['cg_translate_method'] == 'yandexTranslator') {
               		 		        	@wp_automatic_opt_selected($y_langs_c[$i],$camp_translate_from);
               		 		        } ?> ><?php   echo $y_langs[$i]?></option>
               		 		               		 		 
               		 		       <?php
               		 		               		 	
               		 			   $i++;
               		 		  }
               		 		  
               		 		  //Deepl languages
               		 		  $i=0;
               		 		  foreach($d_langs as $lang){
               		 		  	?>
		               		 		               		 	  
               		 		        <option data-filter-val="deeplTranslator"   value="<?php   echo $d_langs_c[$i] ?>"  
               		 		        <?php 
               		 		        
               		 		        if( $camp_general['cg_translate_method'] == 'deeplTranslator') {
               		 		        	@wp_automatic_opt_selected($d_langs_c[$i],$camp_translate_from);
               		 		        } ?> ><?php   echo $d_langs[$i]?></option>
               		 		               		 		 
               		 		       <?php
               		 		               		 	
               		 			   $i++;
               		 		  }
		               		 
		               		 ?>
		               	</select>
		               		 
		               		 To	<select name="camp_translate_to"  class="wp_automatic_lang_select translate_t" style="width:25%;padding:0;">
		               		 
			               		 
			               		 <?php
			               		  
								$i=0;
			               		 foreach($langs as $lang){
			               		 	?>
			               		 	
			               		 		<option  data-filter-val="microsoftTranslator"  value="<?php   echo $langs_c[$i] ?>"  <?php if( $camp_general['cg_translate_method'] == 'microsoftTranslator') @wp_automatic_opt_selected($langs_c[$i],$camp_translate_to) ?> ><?php   echo $langs[$i]?></option>
			               		 	
			               		 	<?php 
									$i++;
			               		 }
			               		 
			               		 // Google Languages output.
			               		 $i=0;
			               		 foreach($g_langs as $lang){
			               		 	?>
			               		 		               		 		               		 	  
               		                <option data-filter-val="googleTranslator"   value="<?php   echo $g_langs_c[$i] ?>"  <?php  if( $camp_general['cg_translate_method'] == 'googleTranslator') @wp_automatic_opt_selected($g_langs_c[$i],$camp_translate_to) ?> ><?php   echo $g_langs[$i]?></option>
               		                		 		               		 		 
               		                <?php
               		                		 		               		 	
               		                  $i++;
               		               }
               		               
               		               // Yandex Languages output.
               		               $i=0;
               		               foreach($y_langs as $lang){
               		               	?>
			               		 		               		 		               		 	  
               		                <option data-filter-val="yandexTranslator"   value="<?php   echo $y_langs_c[$i] ?>"  <?php  if( $camp_general['cg_translate_method'] == 'yandexTranslator') @wp_automatic_opt_selected($y_langs_c[$i],$camp_translate_to) ?> ><?php   echo $y_langs[$i]?></option>
               		                		 		               		 		 
               		                <?php
               		                		 		               		 	
               		                  $i++;
               		               }
               		               
               		               // Deepl Languages output.
               		               $i=0;
               		               foreach($d_langs as $lang){
               		               	?>
			               		 		               		 		               		 	  
               		                <option data-filter-val="deeplTranslator"   value="<?php   echo $d_langs_c[$i] ?>"  <?php  if( $camp_general['cg_translate_method'] == 'deeplTranslator') @wp_automatic_opt_selected($d_langs_c[$i],$camp_translate_to) ?> ><?php   echo $d_langs[$i]?></option>
               		                		 		               		 		 
               		                <?php
               		                		 		               		 	
               		                  $i++;
               		               }
			               		 
			               		 ?>
			               		 
		               	
		               		</select>
		               		
		               		To	<select name="camp_translate_to_2"  class="wp_automatic_lang_select translate_t" style="width:25%;padding:0;">
		               		 
			               		 
			               		 <?php
			               		  
			               		 	// Microsoft Languages output.
									$i=0;
				               		foreach($langs as $lang){
				               		 	?>
				               		 	
				               		 		<option   data-filter-val="microsoftTranslator"  value="<?php   echo $langs_c[$i] ?>"  <?php if( $camp_general['cg_translate_method'] == 'microsoftTranslator') @wp_automatic_opt_selected($langs_c[$i],$camp_translate_to_2) ?> ><?php   echo $langs[$i]?></option>
				               		 	
				               		 	<?php 
										$i++;
				               		}
									
									// Google Languages output.
									$i=0;
									foreach($g_langs as $lang){
									
									?>
												               		 		               		 		               		 	  
               		                <option data-filter-val="googleTranslator"   value="<?php   echo $g_langs_c[$i] ?>"  <?php  if( $camp_general['cg_translate_method'] == 'googleTranslator') @wp_automatic_opt_selected($g_langs_c[$i],$camp_translate_to_2) ?> ><?php   echo $g_langs[$i]?></option>
               		                		 		               		 		 
               		                <?php
               		                		 		               		 	
               		                  $i++;
               		                }
									
               		                // Yandex Languages output.
               		                $i=0;
               		                foreach($y_langs as $lang){
               		                	
               		                	?>
												               		 		               		 		               		 	  
               		                <option data-filter-val="yandexTranslator"   value="<?php   echo $y_langs_c[$i] ?>"  <?php  if( $camp_general['cg_translate_method'] == 'yandexTranslator') @wp_automatic_opt_selected($y_langs_c[$i],$camp_translate_to_2) ?> ><?php   echo $y_langs[$i]?></option>
               		                		 		               		 		 
               		                <?php
               		                		 		               		 	
               		                  $i++;
               		                }

               		                // Deepl Languages output.
               		                $i=0;
               		                foreach($d_langs as $lang){
               		                	
               		                	?>
												               		 		               		 		               		 	  
               		                <option data-filter-val="deeplTranslator"   value="<?php   echo $d_langs_c[$i] ?>"  <?php  if( $camp_general['cg_translate_method'] == 'deeplTranslator') @wp_automatic_opt_selected($d_langs_c[$i],$camp_translate_to_2) ?> ><?php   echo $d_langs[$i]?></option>
               		                		 		               		 		 
               		                <?php
               		                		 		               		 	
               		                  $i++;
               		                }
               		                
			               		 
			               		 ?>
			               		 
		               	
		               		</select>
		                	
		                	
		                	         <div id="field1zzxzx-container" class="field f_100">
							               <div class="option clearfix">
							                    <input name="camp_options[]" id="field2xzx-1" value="OPT_TRANSLATE_TITLE" type="checkbox">
							                    <span class="option-title">
														Translate title also   
							                    </span>
							                    <br>
							               </div>
							               
							               
							               
							               <div class="option clearfix">
							                    <input name="camp_options[]"  value="OPT_TRANSLATE_FTP" type="checkbox">
							                    <span class="option-title">
														If translation got failed set the post status to Pending   
							                    </span>
							                    <br>
							               </div>
							               
									 </div>
		                	
		            </div>
		            
               </div>
		 </div><!-- translation -->
		 
		 
		 <div  class="field f_100">
               <div class="option clearfix">
                    
                    <input data-controls="wpml_lang_letters" name="camp_options[]" id="replace_link" value="OPT_WPML" type="checkbox">
                    <span class="option-title">
							Set a WPML language for posted posts
                    </span>
                    <br>
                    
		            <div id="wpml_lang_letters" class="field f_100">
		               
		               <label>
		                    Two letters language code. for example add "de" for german. 
		               </label>
		               <input value="<?php   echo @$camp_general['cg_wpml_lang']   ?> " name="cg_wpml_lang"    type="text">
		               
		               <div class="option clearfix">
			               <input name="camp_options[]"  value="OPT_LINK_PREFIX" type="checkbox">
		                   <span class="option-title">
								Post item even if there is already a posted one from another campaign (By default same url get posted once)(Beta)      
		                   </span>
		                   <br><div class="description"><small><i>(This will suffix the orignal url to make a new url by adding a parameter named "rand" )</i></small></div>
		               </div>    
		             
		            </div>
		             
               </div>
		 </div>
		 
		
		<div class="clear"></div>
	</div>
</div>
</div>
