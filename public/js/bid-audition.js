
BidAudition = {
    
    
    init: function (params) 
    {
        BidAudition.params = params;
        
        $('#bid-amount').on('blur', function() {
            $(this).toNumber();
            if ($(this).val() < 0) {
                $(this).val('');
                return;
            }
            $(this).formatCurrency({symbol: ''});
        });
        
        $('#use-profile-audio').click(function (e) {
            e.preventDefault();
            var $el = $(this);

            if ($el.hasClass('opened')) {
                return;
            }
            $el.addClass('opened');

            $('.profile-audio-preview-container').slideDown();
            $audioUploadPreview.slideUp('fast', function () {
                soundManager.stopAll();

                $('.track-play').removeClass('ui360').addClass('ui360');
                threeSixtyPlayer.init();

                // Remove any previous uploaded audio
                $('form#bid-form').find('.hidden-audio').remove();
                $('.audio-upload-preview', $('#bid-upload-audio-container')).slideUp();
            });
            $('#upload-audio-btn, #init-record').click(function () {
                $('.profile-audio-preview-container').slideUp('fast', function () {
                    BidAudition.cancelUserTrackSelection();
                });
                $el.removeClass('opened');
            });
        });

        $('.user-audio-upload .js-select-profile-audio').click(function (event) {
            event.stopPropagation();
            event.preventDefault();
            soundManager.stopAll();
            var $button = $(this);
            var $audioRow = $button.parents('.user-audio-upload');
            var slug = $audioRow.data('audio-slug');

            $('.user-audio-upload:not([data-audio-slug="' + slug + '"])').slideUp('fast');
            $('.js-select-profile-audio').hide();

            BidAudition.selectUserTrack(slug);
        });
        
        $('#bid-form').on('submit', function (e, eventObj) 
        {
            e.preventDefault();
            var error = false;
            $('#bid-error').slideUp('fast');
            if ($('#bid-amount').val() < 20) {
                error = 'Your bid amount needs to be more than $20';
            } else if (
                !$('.audio-upload-preview').is(':visible')
                && !$('form#bid-form input[name=selected_user_track]').val()
                && !$('#use-profile-audio').hasClass('opened')
            ) {
                console.log($('form#bid-form input[name=selected_user_track]').val());
                error = 'You must provide audio with your audition';
            }
            
            if (error) {
                    $('#bid-error').text(error);
                    $('#bid-error').slideDown();
            } else {
                var form = $(this);
                
                $.ajax({
                    dataType: "json",
                    type: "POST",
                    url:  form.attr('action'),
                    data: form.serialize(),
                    success: function(data)
                    {
                        if (data.success) {
                            if (data.redirect) {
                                window.location.href = data.redirect;
                            } else {
                                $('.messages-header .amount').html("$" + data.amount);
                                $('#vocalizrModal').modal('hide');
                                App.showSuccess('Successfully placed your bid of $' + data.amount);
                            }
                        }
                    }
                });
            }

        });
        
        $('#init-record').on('click', function (e) {
            e.preventDefault();
            $('#audition-options').slideUp('fast', function () {
                $('#record-start').slideDown('fast', function () {
                    BidAudition.setupRecorder();
                    // Remove any previous uploaded audio
                    $('.audio-upload-preview', $('#bid-upload-audio-container')).slideUp();
                    $('.hidden-audio', $('#bid-form')).remove();
                });
            })
        });
        
        $('.btn-record').on('click', function (e) {
            e.preventDefault();
        });
        
        $('#audition-cancel').on('click', function (e) {
            e.preventDefault();
            $('#record-start').slideUp('fast', function () {
                $('#audition-options').slideDown('fast', function () {
                    $('#recorder-container').css('marginLeft', '-999999px');
                });
            }); 
        });
        
        // Record action
        $('#recorder-record').on('click', function (e) {
            $('.hidden-audio', $('#bid-form')).remove();
            $('#record-actions').addClass('hide');
            e.preventDefault();
            BidAudition.record(); 
        });
        
        // Stop recording action
        $('#recorder-stop, #recorder-record-stop').click(function ()
        {      
            BidAudition.stop();
            BidAudition.showPlay();
            return false; 
        });

        // Play recording action
        $('#recorder-play').on('click', function (e) {
            e.preventDefault();
            BidAudition.showRecordBtn('#recorder-stop');
            BidAudition.play(); 
        });

        $('#record-start-over').on('click', function (e) {
            e.preventDefault();
            // Reset recording
            Recorder.stop();
            
            $('#bid-audio-audition').slideUp('fast', function () {
                $('#record-start').slideDown('fast');
            });
        });

        // Save recording action
        $('#save-audition').on('click', function (e) {
            $('#bid-audio-audition').slideUp('fast', function () {
                BidAudition.upload();
                $('#bid-audio-uploading').slideDown('fast', function () {
                    
                });
            })
            return false; 
        });      
  
    },
    
    setupRecorder: function ()
    {
        if (Recorder._initialized) {
            return;
        }
        Recorder.initialize({
            swfSrc: "/swf/recorder.swf",
            // optional:
            flashContainer: document.getElementById("recorder-container"), // (optional) element where recorder.swf will be placed (needs to be 230x140 px)
            onFlashSecurity: function(){    
                
            }
          });
    },
    
    record: function ()
    {
        $('#recorder-container').css('marginLeft', 'auto');
        Recorder.record({
            start: function() {  
                $('#record-start').slideUp('fast', function () {
                    $('#bid-audio-audition').slideDown('fast');
                });
                BidAudition.showRecordBtn('#recorder-record-stop');
                $('#recorder-container').css('marginLeft', '-99999px');
                
            },
            progress: function(milliseconds){  // will be called in a <1s frequency with the current position in milliseconds
                if (BidAudition.timecode(milliseconds) == "0:30") {
                    BidAudition.showPlay();
                    Recorder.stop();
                }
                $('#recorder-timer').html(BidAudition.timecode(milliseconds));
            }
          });
    },
    
    play: function ()
    {
        Recorder.play({
            finished: function(){               // will be called when playback is finished
                $('#recorder-timer').html('0:00');
                BidAudition.showPlay();
            },
            progress: function(milliseconds){  // will be called in a <1s frequency with the current position in milliseconds
                $('#recorder-timer').html(BidAudition.timecode(milliseconds));
            }
          });
    },
    
    stop: function () {
        Recorder.stop();
    },
            
    upload: function () {
        Recorder.upload({
            method: "POST",
            url: BidAudition.params.recordHandler, 
            params: {
              name: BidAudition.params.recordAudioName,
            },
            success: function(responseText) {           // will be called after successful upload
                // Need to display preview
                $audioUploadPreview = $('.audio-upload-preview', $('#bid-form'));
                $('.track-title', $audioUploadPreview).text('Voice Recording');
                $('.track-play a', $audioUploadPreview).attr('href', '/upload/audio/tmp?f=' + BidAudition.params.recordAudioName + '&r=' + Math.random());
                $('.track-play', $audioUploadPreview).removeClass('ui360').addClass('ui360');
                threeSixtyPlayer.init();
                $('#bid-form').append('<input class="hidden-audio" type="hidden" name="audio_file" value="'+ BidAudition.params.recordAudioName+'" />');
                $('#bid-form').append('<input class="hidden-audio" type="hidden" name="audio_title" value="Voice Recording" />');
            
                $audioUploadPreview.show();
                
                $('#bid-audio-uploading').slideUp('fast', function () {
                    $('#audition-options').slideDown();
                });
                
            },
            error: function(){                  // (not implemented) will be called if an error occurrs
                alert('ERROR!');
            }
          });
    },
            
    showPlay: function() {
        BidAudition.showRecordBtn('#recorder-play');
        $('#record-actions').removeClass('hide');

    },
            
    showRecordBtn: function (id) {
        $('.btn-record', $('#bid-audio-audition')).addClass('hide');
        $(id).removeClass('hide');
    },
            
    timecode: function(ms) {
        var hms = {
            h: Math.floor(ms / (60 * 60 * 1000)),
            m: Math.floor((ms / 60000) % 60),
            s: Math.floor((ms / 1000) % 60)
        };
        var tc = []; // Timecode array to be joined with '.'

        if (hms.h > 0) {
            tc.push(hms.h);
        }

        tc.push((hms.m < 10 && hms.h > 0 ? "0" + hms.m : hms.m));
        tc.push((hms.s < 10 ? "0" + hms.s : hms.s));

        return tc.join(':');
    },

    selectUserTrack: function(slug) {
        var $form = $('form#bid-form');
        var $input = $form.find('input.hidden-audio[name=selected_user_track]');
        if ($input.length === 0) {
            $input = $form.append('<input class="hidden-audio" type="hidden" name="selected_user_track">').find('input[name=selected_user_track]');
        }
        $input.val(slug);
    },

    cancelUserTrackSelection: function() {
        $('.js-select-profile-audio').show();
        $('.user-audio-upload').show();
        $('form#bid-form').find('.hidden-audio').remove();
    }
};