<?php

// Globals
global $post;
global $wpdb;
global $camp_general;
global $post_id;
global $camp_options;
global $post_types;

global $camp_post_category;

?>

<div class="TTWForm-container" dir="ltr">
	<div class="TTWForm">
		<div class="panes">

			 <div class="field f_100">
	               <div class="option clearfix">
	                    <input name="camp_options[]"  value="OPT_NO_DEACTIVATE" type="checkbox">
	                    <span class="option-title">
								Never deactivate keywords for one hour(Not Recommended)<br>(By default if the source has no more new items, posting idles for one hour)      
	                    </span>
	                    <br>
	               </div>
			 </div>
		
			<div class="field f_100">
               <div class="option clearfix">
                    <input name="camp_options[]"  value="OPT_USE_PROXY" type="checkbox">
                    <span class="option-title">
							Use proxies I have added at the settings page for the connection <br>(Usefull if your server is blocked from the source)      
                    </span>
                    <br>
               </div>
		 </div>
		
		<div class="clear"></div>
	</div>
</div>
</div>
