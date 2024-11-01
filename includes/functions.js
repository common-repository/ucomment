var vAuthor = true, vEmail = true, vWebsite = true, vComment = false;

function overrideMoveForm() {
    addComment = {
	moveForm : function(commId, parentId, respondId, postId) {
                var clonedRespondId = respondId;
                var clonedRespond = jQuery('#'+respondId).clone();
                var cancelReply   = clonedRespond.find('#cancel-comment-reply-link');
                var clonedFormId  = clonedRespond.find('form').attr('id') + '-' + commId;
                jQuery('.ucomment-cloned-respond').remove();
                clonedRespond.addClass('ucomment-cloned-respond');
                clonedRespond.find('form').attr('id',  clonedFormId);
                clonedRespond.find('#comment_parent').val(parentId);
                clonedRespond.find('#comment_post_ID').val(postId);
                cancelReply.css('display','');
                cancelReply.click(function() {
                   jQuery('.ucomment-cloned-respond').remove();
                   return false;
                });
                cancelReply.prependTo(clonedRespond);
                clonedRespond.appendTo(jQuery('#'+commId));
                clonedRespond.find('#comment').focus();
		return false;
	},
	I : function(e) {
		return document.getElementById(e);
	}
    }
}

function ajaxCommentForm() {
    if (jQuery('.ucomment-form-error').length == 0)
        jQuery('form').before('<div class="ucomment-form-error"></div>');
    jQuery('body').on('submit', 'form', function() {
        var formEl = jQuery(this);
        var formAction = formEl.attr('action');
        var formData = formEl.serialize();
        if (vAuthor && vEmail && vWebsite && vComment ) {
            jQuery.ajax({  
                type: 'post',  
                url: formAction,  
                data: formData,  
                error: function(data, textStatus){ 
                    jQuery(".ucomment-form-error").html('');
                    var rT = data.responseText.split(/<body[^>]*?>/);
                        rT = rT[1].split(/<\/body>/);
                        rT = rT[0];
                    var errorMessage = jQuery(rT).html();
                    jQuery(".ucomment-form-error").append( errorMessage );
                    return false;
                },  
                success: function(data, textStatus){
                    var rT = data.split(/<body[^>]*?>/);
                        rT = rT[1].split(/<\/body>/);
                        rT = rT[0];    
                    var commentList = jQuery(rT).find('#comments');
                    jQuery('#comments').html(commentList);
                    return false;
                }  
            });     
        }
        return false;
    });
}

function validateCommentForm(rules, placement) {
    if (jQuery('.ucomment-form-error').length == 0)
        jQuery('form').before('<div class="ucomment-form-error"></div>');
    
    jQuery('body').on('blur', '#author', function() {
        jQuery(this).removeClass('ucomment-field-error');
        jQuery('.ucomment-field-message.author').remove();
        if (rules.author.required != undefined && jQuery(this).val().length <= 0) {
            jQuery(this).addClass('ucomment-field-error');
            if (placement == 'field') {
                jQuery(this).after('<div class="ucomment-field-message author">' + rules.author.required + '</div>');
            } else {
                jQuery(".ucomment-form-error").append('<div class="ucomment-field-message author">' + rules.author.required + '</div>');
            } 
            vAuthor = false;
        } else if (rules.author.message != undefined && jQuery(this).val().length < 3) {
            jQuery(this).addClass('ucomment-field-error');
            if (placement == 'field') {
                jQuery(this).after('<div class="ucomment-field-message author">' + rules.author.message + '</div>');
            } else {
                jQuery(".ucomment-form-error").append('<div class="ucomment-field-message author">' + rules.author.message + '</div>');
            } 
            vAuthor = false;
        } else {
            vAuthor = true;
        }
        return false;
    });
    
    jQuery('body').on('blur', '#email', function() {
        jQuery(this).removeClass('isd-field-error');
        jQuery('.ucomment-field-message.email').remove();
        if (rules.email.required != undefined && jQuery(this).val().length <= 0) {
            jQuery(this).addClass('ucomment-field-error');
            if (placement == 'field') {
                jQuery(this).after('<div class="ucomment-field-message email">' + rules.email.required + '</div>');
            } else {
                jQuery(".ucomment-form-error").append('<div class="ucomment-field-message email">' + rules.email.required + '</div>');
            } 
            vEmail = false;
        } else if (rules.email.message != undefined && !isValidEmailAddress(jQuery(this).val())) {
            jQuery(this).addClass('ucomment-field-error');
            if (placement == 'field') {
                jQuery(this).after('<div class="ucomment-field-message email">' + rules.email.message + '</div>');
            } else {
                jQuery(".ucomment-form-error").append('<div class="ucomment-field-message email">' + rules.email.message + '</div>');
            }
            vEmail = false;
        } else {
            vEmail = true;
        }
        return false;
    });
        
    jQuery('body').on('blur', '#url', function() {
        jQuery(this).removeClass('ucomment-field-error');
        jQuery('.ucomment-field-message.website').remove();

        if (rules.website.required != undefined && jQuery(this).val().length <= 0) {
            jQuery(this).addClass('ucomment-field-error');
            if (placement == 'field') {
                jQuery(this).after('<div class="ucomment-field-message website">' + rules.website.required + '</div>');
            } else {
                jQuery(".ucomment-form-error").append('<div class="ucomment-field-message website">' + rules.website.required + '</div>');
            }
            vWebsite = false;
        } else if(rules.website.message != undefined && !/^(https?|ftp):\/\//i.test(jQuery(this).val())) {
            jQuery(this).val('http://'+jQuery(this).val());
            if (!isValidUrl(jQuery(this).val())) {
                jQuery(this).addClass('ucomment-field-error');
                if (placement == 'field') {
                    jQuery(this).after('<div class="ucomment-field-message website">' + rules.website.message + '</div>');
                } else {
                    jQuery(".ucomment-form-error").append('<div class="ucomment-field-message website">' + rules.website.message + '</div>');
                }
            }
            vWebsite = false;
        } else {
            vWebsite = true;
        }
        return false;
    });
        
    jQuery('body').on('blur', '#comment', function() {
        jQuery(this).removeClass('ucomment-field-error');
        jQuery('.ucomment-field-message.comment').remove();
        if (rules.comment.required != undefined && jQuery(this).val().length <= 0) {
            jQuery(this).addClass('ucomment-field-error');
            if (placement == 'field') {
                jQuery(this).after('<div class="ucomment-field-message comment">' + rules.comment.required + '</div>');
            } else {
                jQuery(".ucomment-form-error").append('<div class="ucomment-field-message comment">' + rules.comment.required + '</div>');
            }
            vComment = false;
        } else {
            vComment = true;
        }
        return false;
    });
    
    jQuery('body').on('submit','form', function() {
        if (!vAuthor || !vEmail || !vWebsite || !vComment ) {
            return false;
        }
    });
}

function isValidEmailAddress(emailAddress) {
    var pattern = new RegExp(/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?$/i);
    return pattern.test(emailAddress);
};

function isValidUrl(url) {
    var pattern = new RegExp(/^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i);
    return pattern.test(url);
};

