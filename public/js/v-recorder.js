
vRecorder = {
    
    init: function (params) 
    {
        vRecorder.params = params;
        vRecorder.form = $('#recorder-container').parents('form');
        $('#init-record').on('click', function (e) {
            e.preventDefault();
            
            if ($('#record-start').is(':visible') || $('#audio-recording').is(':visible') || $('#audio-recording-uploading').is(':visible')) {
                return false;
            }
            $('#record-start').slideDown('fast', function () {
                $('#recorder-record').removeClass('hide');
                vRecorder.setupRecorder();
                // Remove any previous uploaded audio
                $('.audio-upload-preview').slideUp();
                $('.hidden-audio', $(vRecorder.form)).remove();
            });
        });
        
        $('.btn-record').on('click', function (e) {
            e.preventDefault();
        });
        
        // Record action
        $('#recorder-record').on('click', function (e) {
            $('.hidden-audio', $(vRecorder.form)).remove();
            $('#record-actions').addClass('hide');
            e.preventDefault();
            vRecorder.record(); 
        });
        
        // Stop recording action
        $('#recorder-stop, #recorder-record-stop').click(function ()
        {      
            vRecorder.stop();
            vRecorder.showPlay();
            return false; 
        });

        // Play recording action
        $('#recorder-play').on('click', function (e) {
            e.preventDefault();
            vRecorder.showRecordBtn('#recorder-stop');
            vRecorder.play(); 
        });

        $('#record-start-over').on('click', function (e) {
            e.preventDefault();
            // Reset recording
            vRecorder.stop();
            $('#recorder-record').removeClass('hide');
            
            $('#audio-recording').slideUp('fast', function () {
                $('#record-start').slideDown('fast');
            });
        });

        // Save recording action
        $('#save-recording').on('click', function (e) {
            $('#audio-recording').slideUp('fast', function () {
                $('#audio-recording-uploading').slideDown('fast', function () {
                    
                });
                 vRecorder.upload();
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
                    $('#audio-recording').slideDown('fast');
                });
                vRecorder.showRecordBtn('#recorder-record-stop');
                $('#recorder-container').css('marginLeft', '-99999px');
                
            },
            progress: function(milliseconds){  // will be called in a <1s frequency with the current position in milliseconds
                if (vRecorder.timecode(milliseconds) == "1:00") {
                    vRecorder.showPlay();
                    Recorder.stop();
                }
                $('#recorder-timer').html(vRecorder.timecode(milliseconds));
            }
          });
    },
    
    play: function ()
    {
        Recorder.play({
            finished: function(){               // will be called when playback is finished
                $('#recorder-timer').html('0:00');
                vRecorder.showPlay();
            },
            progress: function(milliseconds){  // will be called in a <1s frequency with the current position in milliseconds
                $('#recorder-timer').html(vRecorder.timecode(milliseconds));
            }
          });
    },
    
    stop: function () {
        Recorder.stop();
    },
            
    upload: function () {
        Recorder.upload({
            method: "POST",
            url: vRecorder.params.recordHandler, 
            params: {
              name: vRecorder.params.recordAudioName,
            },
            success: function(responseText) {           // will be called after successful upload
                // Need to display preview
                $audioUploadPreview = $('.audio-upload-preview');
                $('.track-title', $audioUploadPreview).text('Voice Recording');
                $('.track-play a', $audioUploadPreview).attr('href', '/upload/audio/tmp?f=' + vRecorder.params.recordAudioName + '&r=' + Math.random());
                $('.track-play', $audioUploadPreview).removeClass('ui360').addClass('ui360');
                threeSixtyPlayer.init();
                $(vRecorder.form).append('<input class="hidden-audio" type="hidden" name="audio_file" value="'+ vRecorder.params.recordAudioName+'" />');
            
                $audioUploadPreview.show();
                
                $('#audio-recording-uploading').slideUp('fast', function () {
                  
                });
                
            },
            error: function(){                  // (not implemented) will be called if an error occurrs
                alert('ERROR!');
            }
          });
    },
            
    showPlay: function() {
        vRecorder.showRecordBtn('#recorder-play');
        $('#record-actions').removeClass('hide');

    },
            
    showRecordBtn: function (id) {
        $('.btn-record').addClass('hide');
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
    }
    
    
}