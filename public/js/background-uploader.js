var BackgroundUploader = {
    init: function(containerId, buttonId) {
        var $container = $('#' + containerId);
        var $button = $('#' + buttonId);

        var uploadUrl = $container.data('url');

        var uploader = new plupload.Uploader({
            runtimes: 'gears,html5,flash,silverlight,browserplus',
            browse_button: buttonId,
            container: containerId,
            max_file_size: '10mb',
            url: uploadUrl,
            unique_names: true,
            multi_selection: false,
            flash_swf_url: '/plupload/js/Moxie.swf',
            silverlight_xap_url: '/plupload/js/Moxie.xap',
            filters: [
                {title: "Images", extensions: "png,jpg,jpeg"}
            ]
        });

        uploader.init();

        uploader.bind('FilesAdded', function(up, files) {
            $('#upload-background-status').fadeIn();
            $('#upload-background-status').text('Uploading...');
            up.refresh(); // Reposition Flash/Silverlight
            uploader.start();
            $button.attr('disabled', 'disabled');
        });

        uploader.bind('UploadProgress', function(up, file) {
            $('#upload-background-status').text('Uploading...');
        });

        uploader.bind('FileUploaded', function(up, file, info) {
            var responseObj = JSON.parse(info.response);
            if (responseObj.img) {
                $('#hero-image').attr('src', responseObj.img);
            }
            $button.removeAttr('disabled');
            $('#upload-background-status').text('');
        })

        this.initGenericUploaders();
    },

    initGenericUploaders: function() {
        $('[data-role=image-upload]').each(function() {

            var $container = $(this);
            var $button = $container;
            var $image = $container.find('[data-role=image]');
            var $status = $container.find('[data-role=upload-status]');
            var uploadUrl = $container.data('url');

            console.log($button, $image);

            var uploader = new plupload.Uploader({
                runtimes: 'gears,html5,flash,silverlight,browserplus',
                browse_button: $button.attr('id'),
                // container: $container.attr('id'),
                max_file_size: '10mb',
                url: uploadUrl,
                unique_names: true,
                multi_selection: false,
                flash_swf_url: '/plupload/js/Moxie.swf',
                silverlight_xap_url: '/plupload/js/Moxie.xap',
                filters: [
                    {title: "Images", extensions: "png,jpg,jpeg"}
                ]
            });

            uploader.init();

            uploader.bind('FilesAdded', function(up, files) {
                $status.fadeIn();
                $status.text('Uploading...');
                up.refresh(); // Reposition Flash/Silverlight
                uploader.start();
                $button.attr('disabled', 'disabled');
            });

            uploader.bind('UploadProgress', function(up, file) {
                $status.text('Uploading...');
            });

            uploader.bind('FileUploaded', function(up, file, info) {
                var responseObj = JSON.parse(info.response);
                if (responseObj.img) {
                    $image.attr('src', responseObj.img);
                    $image.removeClass('is-empty');
                }
                $button.removeAttr('disabled');
                $status.text('Uploaded');
            })
        });
    }
}