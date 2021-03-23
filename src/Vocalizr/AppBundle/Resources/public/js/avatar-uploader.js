var AvatarUploader = {

    init: function(containerId, buttonId) 
    {
        $container = $('#' + containerId);
        $button = $('#' + buttonId);
        
        var username = $container.data('username');
        var uploadUrl = $container.data('url');
        var saveUrl = $button.attr('href');
        
	var uploader = new plupload.Uploader({
		runtimes : 'gears,html5,flash,silverlight,browserplus',
		browse_button : buttonId,
		container : containerId,
		max_file_size : '4mb',
		url : uploadUrl,
                unique_names : true,
                multi_selection: false,
                flash_swf_url : '/plupload/js/Moxie.swf',
                silverlight_xap_url : '/plupload/js/Moxie.xap',
                filters : [
                    {title : "Images", extensions : "png,jpg,jpeg"}
                ]
	});

	uploader.bind('Init', function(up, params) {
		
	});
        

	uploader.init();

	uploader.bind('FilesAdded', function(up, files) {
            // Clear any hidden audio
            $('.avatar-info').hide();
            $('#upload-avatar-status').fadeIn();
            $('#upload-avatar-status').text('Uploading...');
            up.refresh(); // Reposition Flash/Silverlight
            uploader.start();
            $button.attr('disabled', 'disabled');
	});

	uploader.bind('UploadProgress', function(up, file) {
            $('#upload-avatar-status').text('Uploading...');
	});

	uploader.bind('Error', function(up, err) {
            $button.removeAttr('disabled');
            $('#upload-avatar-status').fadeOut('fast', function () {
                message = err.message;
                if (message == "File size error.") {
                    message = 'Error: Max size: ' + up.settings.max_file_size + '';
                }
                $('#upload-avatar-error').html(message);
                $('#upload-avatar-error').fadeIn();
            });
            up.refresh(); // Reposition Flash/Silverlight
        });

	uploader.bind('FileUploaded', function(up, file) 
        {
            $('#upload-avatar-status').fadeOut(function () {
                $('#upload-avatar-status').text('Saving...');
                $('#upload-avatar-status').fadeIn();
                
                // Send request to save image
                $.getJSON(saveUrl + '?f=' + file.target_name, function (data) {
                    
                    // If successful, it will return image file name
                    if (data.img != undefined) {
                        // Update images around the site with new image uploaded
                        $( ".avatar-lg-" + username + ", .avatar-sm-" + username).fadeOut('slow', function () {
                            $( ".avatar-lg-" + username ).attr('src', '/uploads/avatar/large/' + data.img);
                            $( ".avatar-sm-" + username ).attr('src', '/uploads/avatar/small/' + data.img);
                            $( ".avatar-lg-" + username + ", .avatar-sm-" + username ).fadeIn('slow');
                            $('#upload-avatar-status').fadeOut();
                        });
                        
                        // If completed as a task
                        var task = $container.parent().parent();
                        if (task.length && task.hasClass('profile-task'))
                        {
                           $container.parent().slideUp(function() {
                               $container.parent().remove();
                           });
                           task.removeClass('incomplete');
                        }
                        
                    }
                    // If saving error
                    else {
                        $('#upload-avatar-status').fadeOut('fast', function () {
                            error = "Error while saving";
                            if (data.error != undefined) {
                                error = data.error;
                            }
                            $('#upload-avatar-error').text(error);
                            $('#upload-avatar-error').fadeIn();
                        });
                    }
                    
                    $button.removeAttr('disabled');
                });
                
            });            
	});
    }
}