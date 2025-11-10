<?php

// Globals
global $post;
global $wpdb;
global $camp_general;
global $post_id;
global $camp_options;
global $post_types;


global $camp_post_author ;
global  $camp_post_status;
global $camp_post_type;
global $camp_post_custom_k;
global $camp_post_custom_v;




?>

<div class="TTWForm-container" dir="ltr">
	<div class="TTWForm">
		<div class="panes">
		
	
	         <div id="field1zz-container" class="field f_100">

               <label for="field1zz">
                    Post type
               </label>

                 <select name="camp_post_type" id="field1zzz">
   		
   				  <?php 
   				  $post_types = get_post_types(array('public' => true));
									
					foreach($post_types as $post_type){
 
				  	   echo  '<option value="'.$post_type.'"';
				  	
					  	wp_automatic_opt_selected($camp_post_type,$post_type);
				  	
				  	   echo '>'.$post_type ;
									  	
					  echo '</option>';
				  }
				  
		 
				  
				  ?>
                </select>

          </div>
         
         <?php if ( class_exists( 'WooCommerce' ) ) { ?>
         
          <div class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]"  value="OPT_SIMPLE" type="checkbox">
                    <span class="option-title">
							Do not add the product as affiliate but as a simple product
                    </span>
                      
               </div>
		 </div>
		 
		 <div class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]"  value="OPT_PRODUCT_EXTERNAL" type="checkbox">
                    <span class="option-title">
							Add the product as external affiliate product
                    </span>
                      
               </div>
		 </div>
		 
		 <?php } ?>
          
          <div class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]"  data-controls="post_format_c" id="post_format_opt" value="OPT_FORMAT" type="checkbox">
                    <span class="option-title">
							Custom post format ?
                    </span>
                    <br>
                    
		            <div id="post_format_c" class="field f_100">
		               <label for="field6">
		                    Post format name
		               </label>
		               
		                <input value="<?php    echo @$camp_general['cg_post_format']   ?>"  name="cg_post_format" type="text">
		               
		            </div>
		            
               </div>
		 </div>
		 
		 <div class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]"  data-controls="post_parent_c" id="post_parent_opt" value="OPT_PARENT" type="checkbox">
                    <span class="option-title">
							Set a parent page  
                    </span>
                    <br>
                    
		            <div id="post_parent_c" class="field f_100">
		               <label>
		                    Parent page numeric ID
		               </label>
		               
		                <input value="<?php    echo @$camp_general['cg_post_parent']   ?>"  name="cg_post_parent" type="text">
		               
		            </div>
		            
               </div>
		 </div>
		 
          
          <?php  if( function_exists('bbp_insert_topic') ){?>
     	<div class="field f_100">
	 		<div class="option clearfix">
                    
		        <input data-controls="BB_FORUME_c" name="camp_options[]" value="OPT_BB_FORUME" type="checkbox">
		        <span class="option-title">
						Set bbPress forum ID 
		        </span>
		        <br>
		        
		        <div id="BB_FORUME_c" class="field f_100">
		        	 	<label>Numeric forum ID</label> <input value="<?php    echo @$camp_general['cg_bb_fid']   ?>" name="cg_bb_fid" type="text">
		        	
		        		<div class="descripiton">Numeric ID appears at the address bar when editing the forum at the post parameters "post=NUMERIC_ID"<br><br>*Choose "topic" as the post type</div>
		        
		        </div>
                    
		    </div>
		</div>
		<?php } ?>
             
        
    

          
          
          

         <div id="field1zzv-container" class="field f_100">

               <label for="field1zzv">
                    Posts author
               </label>
 				  
 				 <?php wp_dropdown_users(array('name' => 'camp_post_author','selected'=>$camp_post_author , 'role__in' =>array( 'Administrator','Editor','Author','Contributor' ))); ?>
 				 

          </div>
          
          
          <div id="field1zz-container" class="field f_100">
               <label for="field1zz">
                    New post status
               </label>
               <select name="camp_post_status" id="field1zz">
                    <option id="field1-1" value="draft"  <?php @wp_automatic_opt_selected('draft',$camp_post_status) ?> >
                         Draft
                    </option>
                    <option id="field1-2" value="publish"  <?php @wp_automatic_opt_selected('publish',$camp_post_status) ?>  >
                         Published
                    </option> 
                    
                    <option value="private"  <?php @wp_automatic_opt_selected('private',$camp_post_status) ?> >
                         Private
                    </option>
                    
                    <option id="field1-2" value="pending"  <?php @wp_automatic_opt_selected('pending',$camp_post_status) ?>  >
                         Pending
                    </option>
               </select>
          </div>


          
          <div class="field f_100">
               <div class="option clearfix">
                    <input data-controls="alert_original_time" name="camp_options[]"  value="OPT_DRAFT_PUBLISH" type="checkbox">
                    <span class="option-title">
							Add the post as draft then publish after setting featured image/comments/tags <br>(Recommended for other plugins that works on publish)      
                    </span>
                    <br>
                    
                    <div id="alert_original_time" class="field f_100">
		               	
		               <span style="padding-left:20px;"><strong>Alert:</strong></span> Set post to its original time option will not work when this option is active</a>
		               
		            </div>
                    
               </div>
		 </div>
		 
		 <div class="field f_100">
		 <div class="option clearfix">
                    <input name="camp_options[]"   value="OPT_MUST_ENGLISH" type="checkbox">
                    <span class="option-title">
							Set non English posts status as pending (Guessing)   
                    </span>
                    <br>
             </div>
         </div>
         
         		 <div id="custom_fields" class="field f_100">
               <div class="option clearfix">
                    
                    <input name="camp_options[]" id="cusom_fields_option" value="OPT_CUSTOM" type="checkbox">
                    <span class="option-title">
							Add custom fields/Taxonomies to the posts 
                    </span>
                    
                    <br>
                    
		            <div id="custom_fields_c" class="field f_100">
		             
		             <div id="custom_fields_c_f">
		             <div style="margin-bottom:5px" class="supportedTags2"></div> 
		                 <button style="float:right" id="custom_new">+</button>
		               
		               <?php 
		               	if(is_array($camp_post_custom_k) & count($camp_post_custom_k) >0 ){
		             		 
$in=0;
$added=0;
foreach($camp_post_custom_k as $k){
	if(trim($k) != ''){
	$added=1;

	?>
		                <div  class="custom_field_wrap">Field Name <input style="width:100px" value="<?php   echo $k ?>" name="camp_post_custom_k[]" class="no-unify"> 
		                 value <input style="width:200px" value="<?php   echo htmlentities($camp_post_custom_v[$in],ENT_COMPAT, 'UTF-8') ;?>" name="camp_post_custom_v[]" class="no-unify"><br>   </div>
	
	<?php 
	}
	$in++;
}

if($added == 0){
?>
		                <div  class="custom_field_wrap">Field Name <input style="width:100px" value="" name="camp_post_custom_k[]" class="no-unify"> 
		                 value <input style="width:200px" value="" name="camp_post_custom_v[]" class="no-unify"><br>   </div>

<?php 	

}

		               	}else{
?>
		                <div  class="custom_field_wrap">Field Name <input style="width:100px" value="" name="camp_post_custom_k[]" class="no-unify"> 
		                 value <input style="width:200px" value="" name="camp_post_custom_v[]" class="no-unify"><br>   </div>


<?php 		               		

		               	}
		               
		               ?>
		               
		               
		           		</div>
			            <div class="description"><br>*Prefix the field name with "taxonomy_" if you want the value to be added as a custom taxonomy ex "taxonomy_product_tag" adds the value to the taxonomy named product_tag
			            
			            <?php if ( class_exists( 'WooCommerce' ) ) {
			            		
			            		echo '<br><br>*Set the field name to "woo_gallery" if you want to set a product gallery from a rule that contains images <br><br>*Set the field name to "_price" if you want to set product price to an extracted value';
			            	
			            } ?>
			            
			            </div>
		                
		            </div><!-- div1 -->
		           
               </div><!-- div1 -->
		 </div>	<!-- div1 -->	
				 
		
		<div class="clear"></div>
	</div>
</div>
</div>
