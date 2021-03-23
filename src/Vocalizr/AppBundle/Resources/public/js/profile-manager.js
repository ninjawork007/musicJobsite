ProfileManager = {
    
    init: function ()
    {
        $('#user_is_vocalist').on('ifChecked', function(e) {
            $('.vocal-profile').slideDown();
            $('#vocalist-fee').removeClass('hide');
            ProfileManager.userCheckAccountType();
        });
        $('#user_is_vocalist').on('ifUnchecked', function(e) {
            $('.vocal-profile').slideUp();
            $('#vocalist-fee').addClass('hide');
            ProfileManager.userCheckAccountType();
        });
        $('#user_is_producer').on('ifChecked', function(e) {
            $('#producer-fee').removeClass('hide');
            ProfileManager.userCheckAccountType();
        });
        $('#user_is_producer').on('ifUnchecked', function(e) {
            $('#producer-fee').addClass('hide');
            ProfileManager.userCheckAccountType();
        });
        if (!$('#user_is_vocalist').is(':checked')) {
            $('.vocal-profile').hide(); 
        }
        
        // Handle user audio submit, make sure track title isn't empty
        $('#user-upload-audio-form').on('submit', function (e) {
            ProfileManager.userAudioSubmit(e);
        });
        
        $('#geo-location').geocomplete({
            details: "#user-edit-form",
            blur: true,
            detailsAttribute: "data-geo"
        });
        
        $('#user_producer_fee, #user_vocalist_fee').on('blur', function () {
            $(this).toNumber();
            if ($(this).val() < 0) {
                $(this).val('');
                return;
            }
            $(this).formatCurrency({symbol: '', roundToDecimalPlace: 0});
        });
        $('#user_producer_fee, #user_vocalist_fee').formatCurrency({symbol: '', roundToDecimalPlace: 0});

        $('button.title-edit').on('click', function (e) {
            e.preventDefault();

            $(this).closest('div.track-list-item').find('div.track-label').attr('style', 'display:none;');
            $(this).closest('div.track-list-item').find('input.title-save').removeAttr('style');
            $(this).closest('div.user-audio-actions').find('.title-save').removeAttr('style');
            $(this).attr('style', 'display:none;');

            $('button.title-save').on('click', function (e) {
                e.preventDefault();

                let title_save = $(this).closest('div.track-list-item').find('input.title-save');
                $.ajax({
                    url: $(this).attr('data-href'),
                    data: { 'title' : $(title_save).val() },
                    success: function (d) {

                    }
                });

                $(this).closest('div.track-list-item').find('div.track-label').text($(title_save).val());
                $(this).closest('div.track-list-item').find('input.title-save').attr('style', 'display:none;');
                $(this).closest('div.track-list-item').find('div.track-label').removeAttr('style');
                $(this).closest('div.user-audio-actions').find('.title-edit').removeAttr('style');
                $(this).attr('style', 'display:none;');
            });
        });


        
    },
    
    /*
     * If user uploads audio to profile, validate that they have entered
     * a track title
     */
    userAudioSubmit: function (e) {
        $('#user-audio-title-error').addClass('hide');
        if ($('#user-audio-title').val() == "") {
            $('#user-audio-title-error').removeClass('hide');
            $('#user-audio-title').focus();
            e.preventDefault();
            return false;
        }
        return true;
    },
    
    userCheckAccountType: function () {
        if (!$('#user_is_vocalist').is(':checked') && !$('#user_is_producer').is(':checked')) {
            $('#fees-wrap').hide();
        }
        else {
            $('#fees-wrap').show();
        }
    },
       
}