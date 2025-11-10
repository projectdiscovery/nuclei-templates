<?php 

/* ------------------------------------------------------------------------*
 * Function Selected
* ------------------------------------------------------------------------*/
if(! function_exists('wp_automatic_opt_selected')){
	function wp_automatic_opt_selected($src,$val){
		
		if(stristr($src, ',')){
			//array
			$src= array_filter(explode(',', $src));
			
			 
			if(in_array($val, $src)){
				  echo ' selected="selected" ';
			} 
				
			
		}else{
			if (trim($src)==trim($val))   echo ' selected="selected" ';
		}
	
	}
	
	/**
	 * function to display an option in a select box
	 */
	function wp_automatic_opt_display($displayName,$value,$selectedValue,$dataFilterVal=''){

		if(trim($dataFilterVal) != ''){
			$dataFilterValAttr = ' data-filter-val="'.$dataFilterVal.'" ';
		}
		
		  echo '<option '.$dataFilterValAttr.' value="' . $value . '"' ; @wp_automatic_opt_selected( $value, $selectedValue );   echo '>'.$displayName.'</option>';
	
	}
}

/* ------------------------------------------------------------------------*
 * Function Selected
* ------------------------------------------------------------------------*/
if(! function_exists('check_checked')){
	function check_checked($val,$arr){
		//if(! is_array($arr)) return false;
			
		if(in_array($val,$arr)){
			  echo ' checked="checked" ';
		}else{

			return false;
		}
			
	}
}

function wp_automatic_remove_quick_edit( $actions ) {
	global $post;
	if( $post->post_type == 'wp_automatic' ) {
		unset($actions['inline hide-if-no-js']);
	}
	return $actions;
}

if (is_admin()) {
	add_filter('post_row_actions','wp_automatic_remove_quick_edit',10,2);
}

if(! function_exists('wp_automatic_category')){

	/**
	 * Display category and it's childs
	 * @param unknown $cat
	 * @param unknown $childCats
	 */
	 
	function wp_automatic_category($category,&$childCats,$tax,$post_type,$camp_post_category,$prefix = ''){
		
		  echo  '<option   data-tax="'.$tax.'" class="post_category post_'.$post_type.'" value="'.$category->cat_ID.'"';
		wp_automatic_opt_selected($camp_post_category,$category->cat_ID);
		  echo '>'.$prefix.$tax.': '.$category->cat_name . '  (id='.$category->cat_ID.') (posts:'.$category->category_count.')';
		  echo '</option>';
		
		$catChilds = array();
		
		if(isset($childCats[$category->cat_ID]))
		$catChilds = $childCats[$category->cat_ID];
		
		if(count($catChilds) > 0){
			foreach ($catChilds as $childCat){
				wp_automatic_category($childCat,$childCats,$tax,$post_type,$camp_post_category,$prefix .'— ');
			}
		}
		
		
	}
	

}
?>
