<?php

// Globals
global $post;
global $wpdb;
global $camp_general;
global $post_id;
global $camp_options;
global $post_types;

global $camp_replace_link;

?>

<div class="TTWForm-container" dir="ltr">
	<div class="TTWForm">
		<div class="panes">
		
		
					 <div id="field1zz-container" class="field f_100">
		 <div class="option clearfix">
                    
                    <input name="camp_options[]" data-controls="replace_regex"   value="OPT_RGX_REPLACE" type="checkbox">
                    <span class="option-title">
							Search and replace (REGEX enabled) ( pattern1|pattern2) 
                    </span>
                    <br>
                    
		            <div id="replace_regex" class="field f_100">
		               <label for="field6">
		                    Search|Replace one per line
		               </label>
		               <textarea name="cg_regex_replace" ><?php   echo htmlentities($camp_general['cg_regex_replace'],ENT_COMPAT, 'UTF-8' )  ?></textarea>
		            
		            	
		            	<br>
		            	<div class="field f_100">
			            	<input name="camp_options[]"    value="OPT_RGX_REPLACE_PROTECT" type="checkbox">
		                    <span class="option-title">
								Protect html tags  before replacing? 
		                    </span>
		                </div>
		                
		                 <div class="field f_100">    
		                    <input name="camp_options[]"    value="OPT_RGX_REPLACE_WORD" type="checkbox">
		                    <span class="option-title">
								Words replace? (in case above box contining words or sentences) 
		                    </span>
		                    
		                    
	                    </div>
	                    <br>
		                
		                <div class="field f_100">    
		                    <input name="camp_options[]"    value="OPT_RGX_REPLACE_TTL" type="checkbox">
		                    <span class="option-title">
								Apply to titles also? 
		                    </span>
		                   
		                    
	                    </div>
	                    
	                    <div class="field f_100">    
		                    <input name="camp_options[]"    value="OPT_RGX_REPLACE_SEP" type="checkbox">
		                    <span class="option-title">
								Use # as the seprator between search and replace
		                    </span>
		                    <div class="description"><i>search#replace instead of search|replace</i></div>
		                    
	                    </div>
	                    
	                    <br>
	                    
	                     <div class="description"><i>*add "|titleonly" without quotes at the end of the rule if you want to apply it to only titles<br><br>*add "|contentonly" without quotes at the end of the rule if you want to apply it to only the content</i></div>
		            
		            </div>
		            
		            
		            
		            
               </div>
          </div>
		           
         <div id="replace_links" class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]" data-controls="replace_linkc" value="OPT_REPLACE" type="checkbox">
                    <span class="option-title">
							Replace specified keywords in the fetched article with a link
                    </span>
                    <br>
                    
		            <div id="replace_linkc" class="field f_100">
		               <label for="field6">
		                    Link to replace keywords with
		               </label>
		               <input value="<?php   echo $camp_replace_link   ?>" name="camp_replace_link" id="field6"   type="text">
		             
		            </div>
		            
               </div>
		 </div>

		 

          <div id="limit_content" class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]"  data-controls="limit_content_c" id="limit_content_opt" value="OPT_LIMIT" type="checkbox">
                    <span class="option-title">
							Truncate content to a specific number of chars
                    </span>
                    <br>
                    
		            <div id="limit_content_c" class="field f_100">
		               <label for="field6">
		                    Number of characters ?
		               </label>
		               
		                <input value="<?php   echo @$camp_general['cg_content_limit']   ?>" max="20000" min="0" name="cg_content_limit" id="fieldlimit" required="required" class="ttw-range range"
               type="range">
               
               			 
		               
		            </div>
		            
               </div>
		 </div>      


		<div id="limit_title" class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]"  data-controls="limit_title_c" id="limit_title_opt" value="OPT_LIMIT_TITLE" type="checkbox">
                    <span class="option-title">
							Truncate title to a specific number of chars
                    </span>
                    <br>
                    
		            <div id="limit_title_c" >
		              
		              <div class="field f_100" >
			               <label>
			                    Number of characters ?
			               </label>
			               
			                <input value="<?php   echo @$camp_general['cg_title_limit']   ?>" max="20000" min="0" name="cg_title_limit" id="fieldlimit2" required="required" class="ttw-range range" type="range">
			               
		               </div>
		               
		               <div class="field f_100" >
		               <input name="camp_options[]"    value="OPT_LIMIT_NO_DOT" type="checkbox">
		                    <span class="option-title">
								Do not add "..." to the end of the truncated title? 
		                    </span>
		            	
		            		</div>
		            		
		            		 <div class="field f_100" >
		               		<input name="camp_options[]"    value="OPT_LIMIT_NO_TRUN" type="checkbox">
		                    <span class="option-title">
								Remove the last truncated word (not-complete) from the truncated title?
		                    </span>
		            	
		             	</div>
		            
		            </div>
		            
               </div>
		 </div>      
		 
          
		
		<div class="clear"></div>
	</div>
</div>
</div>
