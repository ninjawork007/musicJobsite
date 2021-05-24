

var TaskAudioUploader = {

    init: function(containerId, buttonId) 
    {
        $('#task-user-audio-form').submit(function (e) {
            e.preventDefault();
            
            $('#audio-title-label').remove('span');
            // Make sure audio title isnt empty
            if ($('#audio-title').val() == '') {
                $('#audio-title-label').append(' <span class="error">REQUIRED</span>');
                return false;
            }
            
            form = $(this);
            $.ajax({
               type: "POST",
               url: form.attr('action'),
               data: form.serialize(),
               dataType: 'json',
               success: function(data)
               {
                   if (data.success)
                   {    
                       form.parent().slideUp(function() {
                           form.parent().remove();
                       });
                       form.parent().parent().removeClass('incomplete');
                       
                   }
                   else {
                       $('#task-upload-audio-status').addClass('hide');
                       $('#task-upload-audio-error').removeClass('hide');
                       $('#task-upload-audio-error').html('<span class="error">' + data.message + '</span>');
                       $('#task-upload-btns').slideDown(); 
                   }
               },
               error: function () {
                   $('#task-upload-audio-status').addClass('hide');
                   $('#task-upload-audio-error').removeClass('hide');
                   $('#task-upload-audio-error').html('<span class="error">There has been a problem. Try again</span>');
                   $('#task-upload-btns').slideDown(); 
               }
            });
            
        });
        
        
        container = $('#' + containerId);
        button = $('#' + buttonId);
        uploadBarContainer = $('.upload-audio-bar-container', container);
        uploadBar = $('.upload-audio-bar', uploadBarContainer);
        form = $('#' + container.data('form'));
        
        var uploadUrl = container.data('url');
        var extensions = container.data('extensions');
        
	var uploader = new plupload.Uploader({
		runtimes : 'gears,html5,flash,silverlight,browserplus',
		browse_button : buttonId,
		container : containerId,
		max_file_size : '5mb',
		url : uploadUrl,
                unique_names : true,
                multi_selection: false,
                chunk_size: '500kb',
                flash_swf_url : '/plupload/js/Moxie.swf',
                silverlight_xap_url : '/plupload/js/Moxie.xap',
                filters : [
                    {title : "Audio", extensions : extensions}
                ]
	});

	uploader.bind('Init', function(up, params) {

	});

	uploader.init();

	uploader.bind('FilesAdded', function(up, files) {
            $('#task-upload-audio-error').addClass('hide');
            up.refresh(); // Reposition Flash/Silverlight
            uploader.start();
            $('#task-upload-btns').slideUp(function () {
               uploadBarContainer.slideDown(); 
            });
	});

	uploader.bind('UploadProgress', function(up, file) {
            uploadBar.attr('style', 'width:'+file.percent+'%');
	});

	uploader.bind('Error', function(up, err) 
        {
            uploadBarContainer.slideUp(function () {
               $('#task-upload-btns').slideDown(); 
            });
            
            message = err.message;
            if (message == "File size error.") {
                message = message + ' (Max size: ' + up.settings.max_file_size + ')';
            }
            $('#task-upload-audio-error').html('<span class="error">'+message+'</span>');
            $('#task-upload-audio-error').removeClass('hide');
            up.refresh(); // Reposition Flash/Silverlight
        });

	uploader.bind('FileUploaded', function(up, file) 
        {
            form.append('<input class="hidden-audio" type="hidden" name="audio_file" value="'+file.target_name+'" />');
            uploadBar.attr('style', 'width:100%');
            
            setTimeout(function () {
                uploadBarContainer.slideUp('fast', function () {
                    uploadBar.attr('style', 'width:0px');
                    $('#user-audio-title').slideDown('fast', function () {
                        $('#audio-title').focus();
                    });
                });
                
               
            }, 1000);
            
	});
    }
}