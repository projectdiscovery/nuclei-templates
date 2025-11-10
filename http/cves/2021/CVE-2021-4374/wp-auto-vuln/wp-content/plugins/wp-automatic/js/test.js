$(document).ready(function()
{
	
    //Position the error messages next to input labels
    $.tools.validator.addEffect("labelMate", function(errors, event){
        $.each(errors, function(index, error){
            error.input.first().parents('.field').find('.error').remove().end().find('label').after('<span class="error">' + error.messages[0] + '</span>');
        });

    }, function(inputs){
        inputs.each(function(){
            $(this).parents('.field').find('.error').remove();
        });

    });

	  $("#post").validator({effect:'labelMate'});
	
});