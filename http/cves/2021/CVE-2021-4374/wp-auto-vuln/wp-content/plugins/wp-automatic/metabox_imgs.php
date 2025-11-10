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
		
		
			         <div id="field1zzx-container" class="field f_100">
               <div class="option clearfix">
                    <input   data-controls="wp_automatic_featured_list_opt" name="camp_options[]" id="field2x-1" value="OPT_THUMB" type="checkbox">
                    <span class="option-title">
							Set First image/Vid image as featured image  
                    </span>
                    <br>
                    
                    	
                    
                    	<div  id="wp_automatic_featured_list_opt" class="field f_100">
			                
			                
			                <div class="option clearfix ">
			                    <input data-controls="wp_automatic_strip_div" name="camp_options[]"  value="OPT_THUMB_STRIP" type="checkbox">
			                    <span class="option-title">
										Strip first image from the content after setting it as featured image  
			                    </span>
			                    <br>
			                    
			                    <div class="field f_100" id="wp_automatic_strip_div">
				                    <div  class="option clearfix">
				                    	
				                    	 <input  name="camp_options[]"  value="OPT_THUMB_STRIP_FULL" type="checkbox">
						                    <span class="option-title">
													Actually strip the image from the content (by default the plugin filter the content on display to strip it) 
						                    </span>
						                    <br>
				                    	
				                    </div>
			                    </div>
			                    
			               </div>
			               
			               <div  class="option clearfix">
			                    <input name="camp_options[]" id="field2xz-1" value="OPT_THUMB_CLEAN" type="checkbox">
			                    <span class="option-title">
										Try to generate a name for the image from the post title  
			                    </span>
                    		</div>
                    		
                    		<div  class="option clearfix">
			                    <input name="camp_options[]"  value="OPT_THUMB_ALT" type="checkbox">
			                    <span class="option-title">
										Set title of the image at media library from the post title.  
			                    </span>
                    		</div>
		                
		                <div  class="option clearfix">
			                    <input data-controls="optthumbalt" name="camp_options[]"  value="OPT_THUMB_ALT2" type="checkbox">
			                    <span class="option-title">
										Set the image alt text from original alt text if exists
			                    </span>
			                    
			                    <div id="optthumbalt" >
			                    	
			                    		<input   name="camp_options[]"  value="OPT_THUMB_ALT3" type="checkbox">
				                    <span class="option-title">
											If no alt exists at the source, Set the alt to the post title
				                    </span>
			                    	
			                    </div>
			                    
                    		</div>
		                    
		                <div class="option clearfix">
		                    <input data-controls="minimum_image_width"  name="camp_options[]"  value="OPT_THUMB_WIDTH_CHECK" type="checkbox">
		                    <span class="option-title">
									Check image width and skip images with width below a specifc width in pixels   
		                    </span>

		                    <div id="minimum_image_width">
		                    
		                     <input value="<?php   echo @$camp_general['cg_minimum_width']   ?>" max="1000" min="0" name="cg_minimum_width" id="cg_minimum_width_f"  required="required" class="ttw-range range" type="range">
		                    
		                    </div>
		                    
	                    </div>
	                    
	                    
	                    <input data-controls="wp_automatic_featured_list" name="camp_options[]" id="field2x-1" value="OPT_THUMB_LIST" type="checkbox">
	                    <span class="option-title">
								Set random featured image if no image exists in the content   
	                    </span>
	                    
	                    <br>
	                    
			            <div id="wp_automatic_featured_list" class="field f_100">
			               <label for="field6">
			                    Image list one image url per line 
			               </label>
			               
			            	<textarea name="cg_thmb_list" ><?php   echo @$camp_general['cg_thmb_list']?></textarea>
			            	
			            	 <input  name="camp_options[]"  value="OPT_THUMB_LIST_FORCE" type="checkbox">
			                 <span class="option-title">
										Force using these images and ignore source found images   
			                 </span>
			            	
			            </div>
			            
			            <div class="option clearfix">
		                    <input  name="camp_options[]"  value="OPT_THUM_NELO" type="checkbox">
		                    <span class="option-title">
									Don't save the image to my server, I will use <a href="https://wordpress.org/plugins/featured-image-from-url/" target="_blank">this free plugin</a> to display the featured image from its source   
		                    </span>
	                    </div>
	                    
	                    <div class="option clearfix">
		                    <input  name="camp_options[]"  value="OPT_THUM_NOTYPE" type="checkbox">
		                    <span class="option-title">
									Do not validate content-type when downloading the image (Not recommended)   
		                    </span>
	                    </div>
	                    
						<div class="typepart Feeds Multi  option clearfix">
		                    <input data-controls="og_image_reverse_order" name="camp_options[]"  value="OPT_FEEDS_OG_IMG" type="checkbox">
		                    <span class="option-title">
									Extract the image from the og:image tag (used for facebook thumb)   
		                    </span>
		                    
		                    <div id="og_image_reverse_order">
		                    	<input  name="camp_options[]"  value="OPT_FEEDS_OG_IMG_REVERSE" type="checkbox">
			                    <span class="option-title">
										Skip the og:image if the content already contains an image.   
			                    </span>
			                    	
		                    </div>
		                    
		                    
	                    </div>
	                    
	                    <div class="option clearfix">
		                    <input  name="camp_options[]"  value="OPT_THUM_MUST" type="checkbox">
		                    <span class="option-title">
									If it was not possible to set a featured image, set the post status to pending   
		                    </span>
	                    </div>
	
	                    
                    </div>
                    
               </div>
		 </div>

		 		 
		  
         <div id="field1zzxz-container" class="field f_100">
               <div class="option clearfix">
                    <input data-controls="wp_automatic_clean_imgs" name="camp_options[]" id="field2xz-1" value="OPT_CACHE" type="checkbox">
                    <span class="option-title">
							Download images from the post content to my server  
                    </span>
                    <br>
                    
                    <div  id="wp_automatic_clean_imgs" class="field f_100 ">
	                    
	                    <div class="option clearfix">
		                    <input name="camp_options[]" id="field2xz-1" value="OPT_CACHE_CLEAN" type="checkbox">
		                    <span class="option-title">
									Try to generate names for images from the post title  
		                    </span>
                    		</div>
               		
               			<div class="option clearfix">
			                    <input name="camp_options[]"   value="OPT_FEED_SRCSET" type="checkbox">
			                    <span class="option-title">
										Don't strip srcset attributes from images
			                    </span>
			                    <br>
			             </div>
			             
			             <div class="option clearfix">
			                    <input name="camp_options[]" data-controls="wp_automatic_media_div"   value="OPT_FEED_MEDIA" type="checkbox">
			                    <span class="option-title">
										Add the images to the media library as well (Not recommended)
			                    </span>
			                    <br>
			                    
			                    <div class="field f_100" id="wp_automatic_media_div">
				                    <div  class="option clearfix">
				                    	
				                    	 <input  name="camp_options[]"  value="OPT_FEED_MEDIA_ALL" type="checkbox">
						                    <span class="option-title">
													Allow all thumbnail sizes generation (Not recommended)  
						                    </span>
						                    <br>
				                    	
				                    </div>
				                    
				                    <div class="description">
			                    	Use on your own peril, This option takes longer excusion time, many unnecessary DB records and stored files
			                    </div>
				                    
			                    </div>
			                    
			                    
			             </div>
               		
               		</div>
               		
               		
                    
               </div>
		 </div>
		 
		 <div   class="field f_100">
               <div class="option clearfix">
                    <input name="camp_options[]"   value="OPT_CACHE_REFER_NULL" type="checkbox">
                    <span class="option-title">
							When loading images, set the refer value to null (no refer)   
                    </span>
                    <br>
               </div>
		 </div>
			
	 
		
		<div class="clear"></div>
	</div>
</div>
</div>
