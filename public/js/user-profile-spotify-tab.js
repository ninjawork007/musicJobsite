function VocalizrAppUserProfileSpotifyTab($){

    var self = this;

    this.$playlistContainer = null;
    self.$artistContainer   = null;

    this.init = function() {

        self.$playlistContainer = $('[data-role="spotify-playlist-container"]');
        self.$artistContainer   = $('[data-role="spotify-artist-container"]');

        $('[data-role="add-spotify-playlist"]').on('click', function(){

            var $input       = $('#spotify_playlist');
            var $label       = $('label[for="spotify_playlist"]');
            var currentCount = $('[data-role="spotify-playlist-item"]').length;

            $label.find('span').remove();

            if($input.val().trim() == ''){
                $label.append('<span class="error">Link is required</span>');
                return false;
            }

            $.ajax({
                url: $(this).data('path'),
                data: {
                    link: $input.val(),
                    edit: $(this).data('edit'),
                    id:  $(this).data('id')
                },
                success: function(data){
                    if(data.success) {
                        self.$playlistContainer.html(data.html);
                        $('[data-role="message-container"]').append('<div data-role="new-alert" class="alert alert-success">Successfully added</div>');
                        setTimeout(function(){$('[data-role="new-alert"]').slideUp()}, 5000);

                        if(data.count < 4) {
                            $('[data-role="add-more-spotify-playlists"]').addClass('hidden')
                        } else if(currentCount >= 4) {
                            $('[data-role="add-more-spotify-playlists"]').removeClass('hidden')
                        }

                        $input.val('');
                    } else {
                        $label.find('span').remove();
                        if (data.error) {
                            var message = data.error;
                        } else {
                            var message = 'Incorrect link';
                        }
                        $label.append('<span class="error">' + message + '</span>')
                    }
                }
            })
        });

        self.$artistContainer.find('button').on('click', function(){

            var $input = $('#user_spotify_id').val();
            var $label = $('label[for="user_spotify_id"]');
            var $button = $(this);

            var val = $input.split(':');

            var predefined = $button.attr('data-value');

            var errorMsg = "Incorrect link";
            var action = "edit";

            if (typeof predefined !== "undefined") {
                val = predefined;
                errorMsg = "An error occurred. Try again later.";
                action = "remove";
            } else {
                if ($input.trim() == '' || val[0] != 'spotify' || val[1] != 'artist' || val[2] == '') {
                    $label.append('<span class="error">Incorrect link</span>');
                    return false;
                }
                val = val[2];
            }

            $.ajax({
                url: $(this).data('path'),
                data: {
                    spotifyId: val
                },
                success: function(data){
                    if(data.success) {
                        $('[data-role="message-container"]').append('<div data-role="new-alert" class="alert alert-success">Success</div>');
                        setTimeout(function(){$('[data-role="new-alert"]').slideUp()}, 5000);
                        if (action === "remove") {
                            $('[data-role="spotify.artist.remove"]').hide();
                            $('[data-role="spotify-playlist-item"]').remove();
                            $('#user_spotify_id').val('');
                        } else {
                            $('[data-role="spotify.artist.remove"]').show();
                        }
                    } else {
                        $label.find('span').remove();
                        $label.append('<span class="error">' + errorMsg +'</span>')
                    }
                }
            })
        });

        $('[data-role="add-more-spotify-playlists"]').on('click', function(){

            $.ajax({
                url: $(this).data('path'),
                data: {
                    edit: $(this).data('edit'),
                    id:  $(this).data('id'),
                    offset:  $('[data-role="spotify-playlist-item"]').length
                },
                success: function(data){
                    if(data.success) {
                        self.$playlistContainer.append(data.html);

                        if(data.count < 4) {
                            $('[data-role="add-more-spotify-playlists"]').hide()
                        }

                    } else {
                        $label.find('span').remove();
                        $label.append('<span class="error">Incorrect link</span>')
                    }
                }
            })
        });
    }
}