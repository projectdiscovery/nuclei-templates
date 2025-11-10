<?php

// Globals
global $post;
global $wpdb;
global $camp_general;
global $post_id;
global $camp_options;
global $post_types;

global $camp_post_title;
global $camp_post_content;

?>

<div class="TTWForm-container" dir="ltr">
	<div class="TTWForm">
		<div class="panes">


			<div id="field6-container" class="field f_100">
				<label for="field6"> Post title template </label> <input value="<?php   echo htmlentities($camp_post_title)  ?>" name="camp_post_title" id="field6" required="required" type="text">
			</div>

			<div id="field11-container" class="field f_100">
				<label for="field11"> Post text template <i>(spintax enabled, like {awesome|amazing|Great})</i>
				</label>
				<textarea required="required" rows="5" cols="20" name="camp_post_content" id="field11"><?php   echo $camp_post_content ?></textarea>
				<div class="supportedTags description "></div>
			</div>
			
			 <div  class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]" data-controls = "default_c"  value="OPT_DEFAULT_TAGS" type="checkbox">
                    <span class="option-title">
							Set default values for tags that return empty values
                    </span>
                    <br>
                    
		            <div id="default_c" class="field f_100">
		               <label for="field6">
		                    tag|default_value (one rule per line)
		               </label>
		               
		            	<textarea name="cg_default_tags" ><?php   echo @$camp_general['cg_default_tags'] ?></textarea>
		            
		            <div class="description">for example add "item_salary|Not Available" so if the tag named [item_salary] will return empty, it will return "Not Available" instead.</div>	 
		             	
		            </div>
		            
               </div>
		 </div>

		<div  class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]" data-controls = "incdefault_c"  value="OPT_ADJUST_TAGS" type="checkbox">
                    <span class="option-title">
							Adjust numeric tags values (increase/decrease/multiply)
                    </span>
                    <br>
                    
		            <div id="incdefault_c" class="field f_100">
		               <label for="field6">
		                    tag|tag adjustment equation (one rule per line)
		               </label>
		               
		            	<textarea name="cg_adjust_tags" ><?php   echo @$camp_general['cg_adjust_tags'] ?></textarea>
		            
		            <div class="description">for example add "item_price|item_price * 1.5" so if the tag named [item_price] will return 10, it will return 15 instead.<br><br>*for example add "item_price|item_price + 2" so if the tag named [item_price] will return 10, it will return 12 instead.</div>	 
		             	
		            </div>
		            
               </div>
		 </div>


			<div class="clear"></div>
		</div>
	</div>
</div>
