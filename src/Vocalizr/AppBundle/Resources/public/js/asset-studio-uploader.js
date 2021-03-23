var AssetStudioUploader = {

    init: function (containerId, buttonId) {
        $container = $('#' + containerId);
        $button = $('#' + buttonId);

        var extensionsList = "aif,aiff,wav";
        var maxFileSize = "200mb";

        var uploadUrl = $container.data('url');

        mOxie.Mime.addMimeType("audio/x-aiff,aif aiff"); // notice that it is

        var uploader = new plupload.Uploader({
            runtimes: 'html5,flash,silverlight,browserplus',
            browse_button: buttonId,
            container: containerId,
            max_file_size: maxFileSize,
            url: uploadUrl,
            unique_names: true,
            chunk_size: '10m',
            multi_selection: true,
            flash_swf_url: '/plupload/js/Moxie.swf',
            silverlight_xap_url: '/plupload/js/Moxie.xap',
            filters: [
                {title: "Audio", extensions: extensionsList}
            ]
        });

        uploader.bind('Init', function (up, params) {
            $(".message-file-list").mCustomScrollbar("update");
        });


        uploader.init();

        uploader.bind('FilesAdded', function (up, files) {
            $.each(files, function (i, file) {
                $('#asset-filelist').append(
                    '<div id="' + file.id + '" class="new-asset"><i class="fa fa-upload"></i> ' +
                    file.name + ' (' + plupload.formatSize(file.size) + ') <b>0%</b><br> <div class="asset-progress progress"><div class="progress-bar" style="width: 0;"></div></div>' +
                    '<i class="fa fa-times remove-asset"></i>' +
                    '</div>');
            });
            if ($(".message-file-list").length) {
                $(".message-file-list").mCustomScrollbar("update");
                $(".message-file-list").mCustomScrollbar("scrollTo", "bottom");
                MessageCenter.setHeights();
            }

            $('.remove-asset').on('click', function (e) {
                AssetStudioUploader.removeAsset(e, $(this));
            });

            up.refresh(); // Reposition Flash/Silverlight
            uploader.start();
        });

        uploader.bind('UploadProgress', function (up, file) {
            $('#' + file.id + " b").html(file.percent + "%");
            $('#' + file.id + " .progress-bar").css('width', file.percent + "%");
        });

        uploader.bind('Error', function (up, err) {
            $('#asset-filelist').append("<div>Error: " + err.code +
                ", Message: " + err.message +
                (err.file ? ", File: " + err.file.name : "") +
                "</div>"
            );

            up.refresh(); // Reposition Flash/Silverlight
        });

        uploader.bind('FileUploaded', function (up, file) {
            if ($('#submit-assets').length) {
                $('#submit-assets').removeClass('hide');
                $(window).trigger('vocalizr.assets-uploaded');
            }
            $('#' + file.id + " b").html("100%");
            $('#' + file.id).append('<input type="hidden" name="asset_file[]" value="' + file.target_name + '">');
            $('#' + file.id).append('<input type="hidden" name="asset_file_title[]" value="' + file.name + '">');
        });

    },


    removeAsset: function (e, obj) {
        e.preventDefault();
        $(obj).parent().remove();

        if ($(".message-file-list").length) {
            MessageCenter.setHeights();
        }


        // Count remaining uploaded assets, if zero left
        // then remove submit button
        if ($('.new-asset').length == 0) {
            $('#submit-assets').addClass('hide');
        }
    }
}