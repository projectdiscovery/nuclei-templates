/**
 * Created with Visual Form Builder by 23rd and Walnut
 * www.visualformbuilder.com
 * www.23andwalnut.com
 */


jQuery(document).ready(function()
{

			//selcet all 
			jQuery('.select_all').click(function() {
				jQuery("input:checkbox").attr('checked','checked');
 
					return false;
			});
			
			
			jQuery('.deselect_all').click(function() {
				jQuery("input:checkbox").attr('checked','checked');
 				                    jQuery('input:checkbox').removeAttr('checked');
					return false;
			});
			
			
			
    //Style selects, checkboxes, etc
    //jQuery("select, input:radio, input:file").uniform();

    //Date and Range Inputs
	//jQuery("#field1, #field2 , #field255, #field256, #field1s ").rangeinput();
	    jQuery("#field1, #field2").dateinput();
    /**
     * Get the jQuery Tools Validator to validate checkbox and
     * radio groups rather than each individual input
     */

    jQuery('[type=checkbox]').bind('change', function(){
        clearCheckboxError(jQuery(this));
    });


    //validate checkbox and radio groups
    function validateCheckRadio(){
        var err = {};

        jQuery('.radio-group, .checkbox-group').each(function(){
             if(jQuery(this).hasClass('required'))
                if (!jQuery(this).find('input:checked').length)
                    err[jQuery(this).find('input:first').attr('name')] = 'Please complete this mandatory field.';
        });

        if (!jQuery.isEmptyObject(err)){
            validator.invalidate(err);
            return false
        }
        else return true;

    }





    //clear any checkbox errors
    function clearCheckboxError(input){
        var parentDiv = input.parents('.field');

        if (parentDiv.hasClass('required'))
            if (parentDiv.find('input:checked').length > 0){
                validator.reset(parentDiv.find('input:first'));
                parentDiv.find('.error').remove();
            }
    }




    //Position the error messages next to input labels
    jQuery.tools.validator.addEffect("labelMate", function(errors, event){
        jQuery.each(errors, function(index, error){
            error.input.first().parents('.field').find('.error').remove().end().find('label').after('<span class="error">' + error.messages[0] + '</span>');
        });

    }, function(inputs){
        inputs.each(function(){
            jQuery(this).parents('.field').find('.error').remove();
        });

    });


    /**
     * Handle the form submission, display success message if
     * no errors are returned by the server. Call validator.invalidate
     * otherwise.
     */

    jQuery(".TTWForm").validator({effect:'labelMate'}).submit(function(e){
       var form = jQuery(this), checkRadioValidation = validateCheckRadio();

        if(!e.isDefaultPrevented() && checkRadioValidation){
            jQuery.post(form.attr('action'), form.serialize(), function(data){
                data = jQuery.parseJSON(data);

                if(data.status == 'success'){
                    form.fadeOut('fast', function(){
                        jQuery('.TTWForm-container').append('<h2 class="success-message">Success!</h2>');
                    });

                    /************************************************************************************/
                    /*                                REDIRECTION CODE                                  */
                    /*         Only uncomment the line below if you want to redirect to another page    */
                    /*                          when the form has been submitted                        */
                    /************************************************************************************/

                    //window.location = 'http://www.example.com/somePage.html';
                }
                else validator.invalidate(data.errors);

            });
        }

        return false;
    });

    var validator = jQuery('.TTWForm').data('validator');


});
