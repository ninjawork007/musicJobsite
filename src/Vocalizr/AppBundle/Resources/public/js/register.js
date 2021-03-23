Register = {
    
    init: function () 
    {
            $('#geo-location').geocomplete({
                details: "#register-form",
                types: ['(cities)'],
                blur: true,
                detailsAttribute: "data-geo"
            });
            
            $('.sc-select-track').click(function (e) {
                Register.selectScTrack(e, $(this));
            })
    },
    
    selectScTrack: function (e, obj) {
        e.preventDefault();
        $('#soundcloudModal').modal('hide');
        var $parent = $(obj).parent();
        
        var trackTitle = $('.track-title', $parent).text();
        var href = $('.track-play a', $parent).data('href');
        var scId = $(obj).data('id');
        
        //$('#user-audio-title').val(trackTitle);
        
        // Upload audio preview on page
        $audioPrev = $('.audio-preview');
        $('.track-play a', $audioPrev).attr('href', href + '&r=' + Math.random());
        $('.track-play a', $audioPrev).attr('type', 'audio/mp3');
        $('.track-play', $audioPrev).removeClass('ui360').addClass('ui360');
        $('.track-title', $audioPrev).text(trackTitle);
        
        
        
        // Insert hidden track id to save with form
        $('#register-form .hidden-audio').remove();
        $('#register-form').prepend('<input class="hidden-audio sc-track-id" type="hidden" name="sc_track_id" value="'+scId+'">');
        $('#register-form').prepend('<input class="hidden-audio sc-track-title" type="hidden" name="sc_track_title" value="'+scId+'">');
        
        threeSixtyPlayer.stop();
        threeSixtyPlayer.init();
    }
}