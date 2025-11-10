//Add duplicate button
jQuery(document).ready(function(){
	
	jQuery('.edit a').each(function(index,val){
		
		//getting the id
		jQuery(this).parent().after('<span class="edit wp-automatic-duplicate"><a href="' + jQuery(this).attr('href') +  '" title="Duplicate this item">Duplicate</a> | </span>');
		
	});
	
	//click duplicate button
	jQuery('.row-actions').on('click', '.wp-automatic-duplicate a', function() { 
		
		console.log(jQuery(this).parent().parent());
		
		
		//getting input
		var campName = prompt("New Campaign Name", jQuery(this).parent().parent().parent().find('.row-title').text() + "-Copy");
		if (campName != null) {
			
			jQuery(this).parent().parent().append('<div class="spinner-duplicate spinner is-active"></div>');
 
			jQuery.ajax({
				url : ajaxurl,
				type : 'POST',

				data : {
					action : 'wp_automatic_campaign_duplicate',
					href : jQuery(this).attr('href'),
					campName: campName

				},
				
				success: function(data){
					
					jQuery('.spinner-duplicate').remove();
					
					alert(data);
				} 
			});

			
		}

		
		
		return false;
		
	}); 
	
});

