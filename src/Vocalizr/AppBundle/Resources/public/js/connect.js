$('body').on('click', '.btn-load-more', function (e) {
    e.preventDefault();
    var btn = this;
    $(this).attr('disabled', true);
    $(this).html('<i class="fa fa-spinner fa-spin white-highlight"></i>');
    $.get($(this).attr('href'), function (data) {
        $(btn).remove();
        $('.connection-list').append(data);
    });
});

$('body').on('click', '.remove-connection, .ignore-connection', function (e) {
    e.preventDefault();
    App.showLoading();
    var btn = this;
    
    var jqxhr = $.get($(this).attr('href'), function (data) {
        $('#loading').hide();
        App.showSuccess(data.message);
        $(btn).parents('.connect-row').slideUp(200, function () {
            $(this).remove();
            // If no more pending, then remove title
            if ($('.connection-list-pending .connect-row').length == 0) {
                $('.connection-list-pending').prev().remove();
                $('.connection-list-pending').remove();
            }
        });
    });
    jqxhr.fail(function (data) {
        $('#loading').hide();
        $('#vocalizrModal').html(data.responseText);
        $('#vocalizrModal').modal('show');
    });
});