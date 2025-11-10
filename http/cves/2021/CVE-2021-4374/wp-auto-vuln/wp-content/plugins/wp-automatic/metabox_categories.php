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

			<!--first tab-->
			<div class="contains">

				<div id="field1zz-container" class="field f_100">

					<label for="field1zz"> Post posts to this category </label><br><select style="height: 140px" class="no-unify" name="camp_post_category[]" id="field1zz" multiple>

<?php

// Select categories arguments
$args = array (
		'orderby' => 'name',
		'order' => 'ASC',
		'hide_empty' => 0 
);

foreach ( $post_types as $post_type ) {
	
	// Get categories taxononomies
	$customPostTaxonomies = get_object_taxonomies ( $post_type );
	
	if (count ( $customPostTaxonomies ) > 0) {
		foreach ( $customPostTaxonomies as $tax ) {
			
			// If category list it's items
			if (is_taxonomy_hierarchical ( $tax )) {
				
				$args = array (
						'hide_empty' => 0,
						'taxonomy' => $tax,
						'type' => $post_type 
				);
				
				$categories = get_categories ( $args );
				
				$parentCats = array ();
				$childCats = array ();
				$orderedCats = array ();
				
				// function to display categories
				
				// Get parent cats
				foreach ( $categories as $category ) {
					
					if ($category->parent == 0) {
						
						$parentCats [] = $category;
					} else {
						
						$childCats [$category->parent] [] = $category;
					}
				}
				
				// printing
				foreach ( $parentCats as $parentCat ) {
					wp_automatic_category ( $parentCat, $childCats, $tax, $post_type, $camp_post_category );
				}
			} // hiracial
		} // foreach taxonomy
	}
} // foreach post type

?>
                </select> <input id="cg_camp_tax" type="hidden" value="<?php   echo @$camp_general['cg_camp_tax'] ?>" name="cg_camp_tax" />

					<p>Press CTRL + category to multiselect</p>

				</div>


				<div class="field f_100">
					<div class="option clearfix">

						<input name="camp_options[]" data-controls="keyword_to_cat" value="OPT_KEYWORD_CAT" type="checkbox"> <span class="option-title"> Keyword to category </span> <br>

						<div id="keyword_to_cat" class="field f_100">
							<label for="field6"> Keyword|categoryId (one per line) </label>
							<textarea name="cg_keyword_cat"><?php   echo htmlentities($camp_general['cg_keyword_cat'],ENT_COMPAT, 'UTF-8' )  ?></textarea>
							
							<div class="option clearfix">
							   <input   name="camp_options[]"  value="OPT_KEYWORD_TTL" type="checkbox">
			                    <span class="option-title">
										Check the title as well  
			                    </span>
			                    <br>
		                    </div>
		                    
		                    <div class="option clearfix">
			                    <input   name="camp_options[]"  value="OPT_KEYWORD_NO_CNT" type="checkbox">
			                    <span class="option-title">
										Do not check the content  
			                    </span>
			                    <br>
		                    </div>
		                    
		                    <div class="option clearfix">
			                    <input   name="camp_options[]"  value="OPT_KEYWORD_CAT_FORGET" type="checkbox">
			                    <span class="option-title">
										If matches found, ignore fixed chosen categories above 
			                    </span>
			                    <br>
		                    </div>
							
							<div class="description">
								This option will search the content for the keyword and if exists, it will assign the set category to the post <br>
								<br>*example if you added "sugar|5" without quotes ,The plugin will check the content and if it contains the keyword "sugar" it will assign the post to the category which id=5 <br>
								<br>*example 2 if you added "sugar,diet|5,6" without quotes ,The plugin will check the content and if it contains the keyword "sugar" and the keyword "diet" it will assign the post to the category which id=5 and also the category with id=6<br>
								<br>*Look at the categories above for the correct numeric id
							</div>
							
							

						</div>

					</div>
				</div>


		<div  class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]" data-controls = "tags_c"  value="OPT_ADD_TAGS" type="checkbox">
                    <span class="option-title">
							Tag the posts with specific tags
                    </span>
                    <br>
                    
		            <div id="tags_c" class="field f_100">
		               <label for="field6">
		                    Tags(one per line)
		               </label>
		               
		            	<textarea name="cg_post_tags" ><?php   echo @$camp_general['cg_post_tags'] ?></textarea>
		            	
		            	<input name="camp_options[]" data-controls="random_tags_count"   value="OPT_RANDOM_TAGS" type="checkbox">
		            	
		            	<span class="option-title">
							Randomly pick tags from the box
                    	</span>
                    	
                    	
                    	<div id="random_tags_count" class="field f_100">
				               <label for="field6">
				                    Number of Tags ?
				               </label>
				               
				                <input value="<?php   echo @$camp_general['cg_tags_limit']   ?>" max="1000" min="1" name="cg_tags_limit" required="required" id="random_tags_count_field" class="ttw-range range"
		               type="range">
				               
		            </div>
                    	
		            	
		            </div>
		            
               </div>
		 </div>
		 
		 
		<div id="field1zz-container" class="field f_100">
               <div class="option clearfix">
                    <input   name="camp_options[]" id="field2-1" value="OPT_TITLE_TAG" type="checkbox">
                    <span class="option-title">
							Set title words as tags 
                    </span>
                    <br>
               </div>
		 </div>
		 
		 
		 <div class="field f_100">
		 <div class="option clearfix">
                    
                    <input name="camp_options[]" data-controls="keyword_to_tag" value="OPT_KEYWORD_TAG" type="checkbox">
                    <span class="option-title">
							Keyword to tag 
                    </span>
                    <br>
		            <div id="keyword_to_tag" class="field f_100">
		               <label for="field6">
		                    Keyword|tag (one per line)
		               </label>
		               <textarea name="cg_keyword_tag" ><?php   echo htmlentities($camp_general['cg_keyword_tag'] ,ENT_COMPAT, 'UTF-8' )  ?></textarea>
		           	
		           		<div class="option clearfix">
							   <input   name="camp_options[]"  value="OPT_KEYWORD_TTL_TAG" type="checkbox">
			                    <span class="option-title">
										Check the title as well  
			                    </span>
			                    <br>
		                    </div>
		                    
		                    <div class="option clearfix">
			                    <input   name="camp_options[]"  value="OPT_KEYWORD_NO_CNT_TAG" type="checkbox">
			                    <span class="option-title">
										Do not check the content  
			                    </span>
			                    <br>
		                    </div>
		           
		            	<div class="description">
		            		This option will search the content for the keyword and if exists, it will tag the post with the set tag
		            		<br><br>*example if you added "Messi|Sport" without quotes ,The plugin  will check the content and if it contains the keyword "Messi" it tag the post with "Sport"
		            		<br><br>*example2 if you added "Messi,barca|Sport,Barcelona" without quotes ,The plugin  will check the content and if it contains the keyword "Messi" && "barca" it tag the post with "Sport" & "Barcelona"
		            	</div>
		            
		            </div>
		            
               </div>
          </div>  
          
          
          		 <div class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]" data-controls="taxonmy_c"   value="OPT_TAXONOMY_TAG" type="checkbox">
                    <span class="option-title">
							Set custom taxonomy for tags (If you are using a custom post type)
                    </span>
                    <br>
                    
		            <div id="taxonmy_c" class="field f_100">
		               <label for="field6">
		                    Taxonomy name 
		               </label>
		               <input value="<?php   echo $camp_general['cg_tag_tax']   ?>" name="cg_tag_tax"  type="text">
		             	<div class="description">Visit the tags page and read the taxonomy value and paste here. for example woo-commerce products tags page link contains "taxonomy=product_tag&post_type=product" then the taxonomy is product_tag</div>
		            </div>
		            
               </div>
		 </div>


			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>
