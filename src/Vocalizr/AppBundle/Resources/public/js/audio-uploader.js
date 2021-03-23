var AudioUploader = {
    init: function(containerId, buttonId) {
        $container = $('#' + containerId);
        $button = $('#' + buttonId);
        $uploadBarContainer = $('.upload-audio-bar-container', $container);
        $uploadBar = $('.upload-audio-bar', $uploadBarContainer);
        $form = $('#' + $container.data('form'));
        $audioUploadPreview = $('.audio-upload-preview', $container);
        
        var uploadUrl = $container.data('url');
        var extensions = $container.data('extensions');
        
        // Setup cancel audio button
        $('.remove', $container).on('click', function (e) {
            e.preventDefault();
            // Clear any hidden audio
            $('.hidden-audio', $form).remove();
            $audioUploadPreview.slideUp();
            soundManager.stopAll();
        });

        // This condition for only one case
        // If file uploaded from "Studio Message Board"
        var isUploadedFromSMB = 0;
        if ($container.data('studio-message-board') === 'yes') {
            isUploadedFromSMB = 1;
        }
        
        var uploader = new plupload.Uploader({
            runtimes : 'gears,html5,flash,silverlight,browserplus',
            browse_button : buttonId,
            container : containerId,
            max_file_size : '30mb',
            url : uploadUrl + '?isUploadedFromSMB=' + isUploadedFromSMB,
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
            //$('#filelist').html("<div>Current runtime: " + params.runtime + "</div>");
        });

        uploader.init();

        uploader.bind('FilesAdded', function(up, files) {
            if ($('#record-start').length) {
                $('#record-start').slideUp();
                $('#record-action-container').addClass('hide');
                $('#audio-recording').slideUp();
                if (Recorder._initialized) {
                    Recorder.stop();
                }
            }

            // Clear any hidden audio
            $('.hidden-audio', $form).remove();
            $('.badge-soundcloud', $form).remove();
            $audioUploadPreview.slideUp();

            // Clear any errors
            $label = $('[for="'+$container.attr('id')+'"]');
            if ($label.length > 0) {
                $label.find('.error').remove();
            }

            up.refresh(); // Reposition Flash/Silverlight
            uploader.start();
            //$uploadBarContainer.show();
            $uploadBarContainer.slideDown();
	    });

        uploader.bind('UploadProgress', function(up, file) {
            $uploadBar.attr('style', 'width:'+file.percent+'%');
        });

        uploader.bind('Error', function(up, err) {
            message = err.message;
            if (message == "File size error.") {
                message = message + ' (Max size: ' + up.settings.max_file_size + ')';
            }
            $label = $('[for="'+$container.attr('id')+'"]');
            if ($label.length > 0) {
                $label.find('.error').remove();
                $label.append(' <span class="error">'+message+'</span>');
            }
            else {
                alert(message);
            }
            /*
            $('.upload-audio', $container).text("Error: " + err.code +
                    ", Message: " + err.message +
                    (err.file ? ", File: " + err.file.name : ""));
            */
            //$('.upload-audio', $container).removeClass('hide');
            up.refresh(); // Reposition Flash/Silverlight
        });

        uploader.bind('FileUploaded', function(up, file) {
            var fileTargetName = file.target_name;
            var fileName       = file.name;

            // If file uploaded from "Studio Message Board"
            // And has AIFF extension
            if (isUploadedFromSMB && file.type === 'audio/x-aiff') {
                fileTargetName = file.id + '.mp3';
            }

            $form.append('<input class="hidden-audio" type="hidden" name="audio_file" value="' + fileTargetName + '" />');
            $form.append('<input class="hidden-audio" type="hidden" name="audio_title" value="' + fileName + '" />');
            $uploadBar.attr('style', 'width:100%');

            setTimeout(function () {
                $uploadBarContainer.slideUp('fast', function () {
                    $uploadBar.attr('style', 'width:0px');

                    $('.track-title', $audioUploadPreview).text(fileName);
                    $('.track-play a', $audioUploadPreview).attr('href', '/upload/audio/tmp?f=' + fileTargetName);
                    $('.track-play', $audioUploadPreview).removeClass('ui360').addClass('ui360');
                    threeSixtyPlayer.init();

                    $audioUploadPreview.slideDown('fast', function () {
                        // if on user account profile page
                        if ($('#user-audio-title').length > 0) {
                            $('#user-audio-title').focus();
                        }
                    });

                });
            }, 1000);
        });
    }
};