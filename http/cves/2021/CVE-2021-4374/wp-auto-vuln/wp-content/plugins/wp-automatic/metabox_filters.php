<?php

// Globals
global $post;
global $wpdb;
global $camp_general;
global $post_id;
global $camp_options;
global $post_types;

global $camp_post_exact;
global $camp_post_execlude;

?>

<div class="TTWForm-container" dir="ltr">
	<div class="TTWForm">
		<div class="panes">
		
					   <div id="exact_match" class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]" id="exact_option" value="OPT_EXACT" type="checkbox">
                    <span class="option-title">
							Only post the article if it contains one or more of specific words
                    </span>
                    <br>
                    
		            <div id="exact_match_c" class="field f_100">
		               <label for="field6">
		                    Exact match words (one word per line )
		               </label>
		               
		            	<textarea name="camp_post_exact" ><?php   echo $camp_post_exact?></textarea>
		            	
		            	<div class="option clearfix">
			            	<input name="camp_options[]" id="exact_option" value="OPT_EXACT_AFTER" type="checkbox">
		                    <span class="option-title">
									Apply to final content after filling the template (by default applies to content just fetched from the source)
		                    </span>
	                    </div>
	                    
	                    <div class="option clearfix">
		                    <input name="camp_options[]"  value="OPT_EXACT_TITLE_ONLY" type="checkbox">
		                    <span class="option-title">
									Only check at the title (by default the title and content get checked)
		                    </span>
	                    </div>
	                    
	                    <div class="option clearfix">
		                    <input name="camp_options[]"  value="OPT_EXACT_STR" type="checkbox">
		                    <span class="option-title">
									Exact string match (by default REGEX word match is used)
		                    </span>
	                    </div>
	                    
	                    <div class="option clearfix">
		                    <input name="camp_options[]"  value="OPT_EXACT_ALL" type="checkbox">
		                    <span class="option-title">
									Post must contain all words (By default if any word exists)
		                    </span>
	                    </div>
		            	
		            </div>
		            
               </div>
		 </div>
		 
		 
		 		
		 <div id="exact_execlude" class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]" id="execlude_option" value="OPT_EXECLUDE" type="checkbox">
                    <span class="option-title">
							Skip the post if it contains one or more of these words
                    </span>
                    <br>
                    
		            <div id="exact_execlude_c" class="field f_100">
		               <label for="field6">
		                    banned words (one word per line )
		               </label>
		               
		            	<textarea name="camp_post_execlude" ><?php   echo $camp_post_execlude ?></textarea>
		            	
		            		<div class="option clearfix">
			            		<input name="camp_options[]" id="exact_option" value="OPT_EXECLUDE_AFTER" type="checkbox">
		                    <span class="option-title">
									Apply to final content after filling the template (by default applies to content just fetched from the source)
		                    </span>
	                    </div>
	                    
	                    <div class="option clearfix">
			            		<input name="camp_options[]" id="exact_option" value="OPT_EXECLUDE_TITLE_ONLY" type="checkbox">
		                    <span class="option-title">
									Only check at the title (By default, it checks the title and the content)
		                    </span>
	                    </div>
	                    
	                    <div class="option clearfix">
		                    <input name="camp_options[]"  value="OPT_EXCLUDE_EXACT_STR" type="checkbox">
		                    <span class="option-title">
									Exact string match (by default REGEX word match is used)
		                    </span>
	                    </div>
		            	
		            </div>
		            
               </div>
		 </div>
		 
		 <div class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]" data-controls="exact_match_regex_c" value="OPT_EXACT_REGEX" type="checkbox">
                    <span class="option-title">
							Only post the item if it matches one of these specific REGEX (Regular expressions)
                    </span>
                    <br>
                    
		            <div id="exact_match_regex_c" class="field f_100">
		               <label for="field6">
		                   Escapped regular expression without delimiter 
		               </label>
		               
		            	<textarea name="cg_camp_post_regex_exact" ><?php   echo @$camp_general['cg_camp_post_regex_exact']?></textarea>
		            	<div class="description">*default delimiter is {} <br>*one REGEX per line</div>
 		            </div>
		            
               </div>
		 </div>
		 
		 <div class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]" data-controls="exclude_match_regex_c" value="OPT_EXCLUDE_REGEX" type="checkbox">
                    <span class="option-title">
							Skip the post if the item matches one of these specific REGEX (Regular expressions)
                    </span>
                    <br>
                    
		            <div id="exclude_match_regex_c" class="field f_100">
		               <label for="field6">
		                   Escapped regular expression without delimiter 
		               </label>
		               
		            	<textarea name="cg_camp_post_regex_exclude" ><?php   echo @$camp_general['cg_camp_post_regex_exclude']?></textarea>
		            	<div class="description">*default delimiter is {} <br>*one REGEX per line</div>
 		            </div>
		            
               </div>
		 </div>
		 
		 <div class="field f_100">
		    <div class="option clearfix">
                    <input name="camp_options[]"   value="OPT_FEED_TITLE_SKIP" type="checkbox">
                    <span class="option-title">
							Skip the post if there is there is already a published one with same title in the database
                    </span>
                    <br>
             </div>
         </div>
         
         <div class="field f_100">
         	<div class="option clearfix">
								                    
	                    <input name="camp_options[]"  data-controls="limit_min_length_c"   value="OPT_MIN_LENGTH" type="checkbox">
	                    <span class="option-title">
								Skip posts if shorter than a specific length
	                    </span>
	                    <br>
	                    
			            <div id="limit_min_length_c" class="field f_100">
			               <label>
			                    Minimum number of characters ?
			               </label>
			               
			                <input value="<?php   echo @$camp_general['cg_min_length']   ?>" max="20000" min="0" name="cg_min_length" required="required" class="ttw-range range" id="cg_min_length" type="range">
			               
			            </div>
			            
	               </div>
         </div>
		
		  
		
		<div class="clear"></div>
	</div>
</div>
</div>
