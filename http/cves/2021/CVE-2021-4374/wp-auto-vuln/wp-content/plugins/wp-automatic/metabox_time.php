<?php
 
//adding metabox to gallery items
add_action( 'admin_menu', 'wp_automatic_create_meta_box3' );

function wp_automatic_create_meta_box3(){
	add_meta_box( 'wp_automatic_page-meta-boxes3', 'Update Frequency', 'wp_automatic_page_meta_boxes3', 'wp_automatic', 'side', 'high' );
}

function wp_automatic_page_meta_boxes3(){
	
	wp_automatic_page_meta_boxes_pin3();
}

//metabox content 
function wp_automatic_page_meta_boxes_pin3(){
	
	//reading meta values
	global $post;
	global  $wpdb;
	$prefix=$wpdb->prefix;
	$post_id=$post->ID;
	$query="select * from {$prefix}automatic_camps where camp_id='$post_id'";
	$res=$wpdb->get_results($query);
	if (count($res) > 0 ){
		$res=$res[0];
 
		if( stristr($res->camp_general, 'a:') ) $res->camp_general=base64_encode($res->camp_general);
		$camp_general=unserialize(base64_decode($res->camp_general));
		$camp_general=array_map('wp_automatic_stripslashes', $camp_general);
		if(! is_array($camp_general)) $camp_general=array();
	}else{
		$camp_general = array('cg_update_every'=>60 ,'cg_update_unit'=> 1,'cg_custom_start' => '' , 'cg_custom_start_minutes' => '' , 'cg_custom_end_minutes' => '', 'cg_custom_end' => ''  , 'cg_custom_start_am_pm' => ''  , 'cg_custom_end_am_pm' => '');
	}	
 
	?>
	
	<div class="wp_attachment_details TTWForm" dir="ltr" style="width:100%">
	
 
		   
		   <div id="update_slide">  
		   <div id="field5zzzg-container" class="field f_100 ">
               <label for="field5zzzg">
                      Try to add a new post every
               </label>
               <input value="<?php   echo $camp_general['cg_update_every']   ?>" id="field5zzzg" max="1000" min="1" name="cg_update_every" required="required" class="ttw-range range"
               type="range">
           </div> 
           
           	<div id="field-camp_type-container" class="field f_100" style="margin-top:10px" >
				<label for="field-camp_type">
					Update Unit  
					
				</label>
				<select name="cg_update_unit" id="update_unit">
					<option  value="1"  <?php  wp_automatic_opt_selected('1',$camp_general['cg_update_unit']) ?> >Minutes</option> 
					<option  value="60"  <?php  wp_automatic_opt_selected('60',$camp_general['cg_update_unit']) ?> >Hours</option>					 
					<option  value="1440"  <?php  wp_automatic_opt_selected('1440',$camp_general['cg_update_unit']) ?> >Days</option>
				</select>
				
				<?php global $wpAutomaticDemo; 
					  		if($wpAutomaticDemo){
					  			echo '<span style="color:orange;">Note: Automatic posting does not work in demo mode. You will need to click the run now button manually.</span>';
					  		}
					?>
				
			</div>
			 
				<div id="field-camp_opt-container" class="field f_100" >
				     <div class="option clearfix">
				         <input   data-controls="div_custom_int"   name="camp_options[]" id="field-camp_opt-1" value="OPT_CUSTOM_INTERVAL" type="checkbox">     
				          <span class="option-title">
				 			 Custom posting time
				          </span>
				          <br>
				     
				          <div id="div_custom_int"  class="field f_100">
				          	
				          	 <div id="field-cg_custom_start-container" class="field f_100 " >
				          	 	<label for="field-cg_custom_start">
				          	 		Starting post time :
				          	 	</label>
				          	 	<br>
				          	 	<select name="cg_custom_start" id="field1zz" class= "no-unify" style="width:100px">
				          	 		<?php 
				          	 		for($i = 1;$i< 13; $i++){
				          	 		?>
				          	 		
				          	 		<option  value="<?php echo $i ?>"  <?php wp_automatic_opt_selected( $i ,$camp_general['cg_custom_start']) ?> ><?php echo $i ?></option>
				          	 		
				          	 		<?php }?>
 				          	 	</select>
 				          	 	:
 				          	 	<select name="cg_custom_start_minutes" id="field1zz" class= "no-unify" style="width:100px">
				          	 		<?php 
				          	 		for($i = 0;$i< 60; $i++){
				          	 		?>
				          	 		
				          	 		<option  value="<?php echo $i ?>"  <?php wp_automatic_opt_selected( $i ,$camp_general['cg_custom_start_minutes']) ?> ><?php echo  sprintf("%02d", $i); ?></option>
				          	 		
				          	 		<?php }?>
 				          	 	</select>
 				          	 	
 				          	 	<select name="cg_custom_start_am_pm" id="field1zz" class= "no-unify" style="width:50px">
				          	 		<option  value="am"  <?php wp_automatic_opt_selected( 'am' ,$camp_general['cg_custom_start_am_pm']) ?> >am</option>
				          	 		<option  value="pm"  <?php wp_automatic_opt_selected( 'pm' ,$camp_general['cg_custom_start_am_pm']) ?> >pm</option>
 				          	 	</select>
 				          	 	
				          	 </div>
				          	 
				          	 <div id="field-cg_custom_end-container" class="field f_100 " >
				          	 	<label for="field-cg_custom_end">
				          	 		Ending post time :
				          	 	</label>
				          	 	<br>
				          	 	<select name="cg_custom_end"   class= "no-unify" style="width:100px">
				          	 		<?php 
				          	 		for($i = 1;$i< 13; $i++){
				          	 		?>
				          	 		
				          	 		<option  value="<?php echo $i ?>"  <?php wp_automatic_opt_selected( $i ,$camp_general['cg_custom_end']) ?> ><?php echo $i ?></option>
				          	 		
				          	 		<?php }?>
 				          	 	</select>
 				          	 	:
 				          	 	<select name="cg_custom_end_minutes" id="field1zz" class= "no-unify" style="width:100px">
				          	 		<?php 
				          	 		for($i = 0;$i< 60; $i++){
				          	 		?>
				          	 		
				          	 		<option  value="<?php echo $i ?>"  <?php wp_automatic_opt_selected( $i ,$camp_general['cg_custom_end_minutes']) ?> ><?php echo  sprintf("%02d", $i); ?></option>
				          	 		
				          	 		<?php }?>
 				          	 	</select>
 				          	 	
 				          	 	<select name="cg_custom_end_am_pm"   class= "no-unify" style="width:50px">
				          	 		<option  value="am"  <?php wp_automatic_opt_selected( 'am' ,$camp_general['cg_custom_end_am_pm']) ?> >am</option>
				          	 		<option  value="pm"  <?php wp_automatic_opt_selected( 'pm' ,$camp_general['cg_custom_end_am_pm']) ?> >pm</option>
 				          	 	</select>
 				          	 	
				          	 </div> 
				          	
				          </div>
				     
				     </div>
				</div> 
			 
			</div>
           
           <div style="clear:both"></div>	 
           
     </div>
	
	<?php  
	 
}