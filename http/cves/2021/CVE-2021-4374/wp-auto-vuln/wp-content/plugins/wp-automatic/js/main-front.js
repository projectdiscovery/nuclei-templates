jQuery(document).ready(function(){
	
	var attrName = 'data-a-src' ;
	if(jQuery('.wp_automatic_gallery').attr('data-a-src') == undefined) attrName = 'src'; 
	
	var wp_automatic_main_scr = jQuery('.wp_automatic_gallery:first-child').attr(attrName);
	 
	jQuery('.wp_automatic_gallery:first-child').before('<div class="wp_automatic_gallery_wrap"><div style="background-image:url(\'' + wp_automatic_main_scr +'\')" class="wp_automatic_gallery_main" ></div><div class="clear"></div></div>');
	
	
	jQuery('.wp_automatic_gallery').each(function(){
		
		jQuery('.wp_automatic_gallery_wrap').append('<div class="wp_automatic_gallery_btn" style="background-image:url(\''+ jQuery(this).attr( attrName ) + '\')" ></div>');
		jQuery(this).remove();
	});
	
	
	jQuery('.wp_automatic_gallery_btn:last').after('<div style="clear:both"></div><br>');
	
 	
	
	jQuery('.wp_automatic_gallery_btn').click(function(){
	  
	  jQuery('.wp_automatic_gallery_main').css('background-image', jQuery(this).css('background-image')  );
	  
	});

	
});