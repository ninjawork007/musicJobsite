$('body').on('click', '[data-href]', function (e) {
    window.location = $(this).data('href');
});

$('body').on('submit', '.frm-subscribe', function (e) {
    e.preventDefault();
    var text = $('button', this).text();
    var form = $(this);
    
    var email = $('.input-subscribe-email').val();
    if (!email) {
        return false;
    }
    
    $('button', form).text("Loading...");
    $('button', form).attr('disabled', true);
    
    $('.subscribe-msg').addClass('hide');
    $('.subscribe-msg').removeClass('alert-success');
    $('.subscribe-msg').removeClass('alert-danger');

    $.post($(this).attr('action'), $(this).serialize(), function (data) {
        $('button', form).text(text);
        $('button', form).removeAttr('disabled');
    
        if (data.success !== undefined) {
            $('.subscribe-msg').addClass('alert-success');
            $('.subscribe-msg').text("You are now subscribed to VMag!");
            $('.subscribe-msg').removeClass('hide');
        }
        else {
            $('.subscribe-msg').addClass('alert-danger');
            $('.subscribe-msg').text(data.error);
            $('.subscribe-msg').removeClass('hide');
        }
    
        console.log(data);
        

    });
});