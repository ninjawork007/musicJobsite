
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
        
        $('#bid-form').on('submit', function (e, eventObj) 
        {
            $('#bid-error').slideUp('fast', function ()
            {
                if ($('#bid-amount').val() < 20)
                {
                    $('#bid-error').text('Your bid amount needs to be more than 20');
                    $('#bid-error').slideDown();
                    e.preventDefault();
                    return false;
                } 
            });
        });
        
        
        $('#init-record').on('click', function (e) {
            e.preventDefault();
            $('#audition-options').slideUp('fast', function () {
                $('#bid-audio-audition').slideDown('fast', function () {
                    BidAudition.setupRecorder();
                });
            })
        });
        
        $('.btn-record').on('click', function (e) {
            e.preventDefault();
        });
        
        $('#audition-cancel').on('click', function (e) {
            recorder().sendCancel();
            e.preventDefault();
            $('#bid-audio-audition').slideUp('fast', function () {
                $('#audition-options').slideDown('fast', function () {
                    
                });
            }); 
        });
        
        // Record action
        $('#recorder-record').on('click', function (e) {
            $('.hidden-audio', $('#bid-form')).remove();
            e.preventDefault();
            BidAudition.showRecordBtn('#recorder-stop');
            
            $('#record-start-msg').addClass('hide');
            $('#record-status').removeClass('hide');
            $('#record-actions, #record-again').addClass('hide');
            recorder().sendRecord();
            $("#recorder-meter-bar").addClass('recording');
            return false; 
        });
        
        // Stop recording action
        $('#recorder-stop').click(function ()
        {      
            recorder().sendStop();
            recorder().sendPlay();
            return false; 
        });

        // Play recording action
        $('#recorder-play').click(function () {
            BidAudition.showRecordBtn('#recorder-pause');
            recorder().sendPlay();
            return false; 
        });

        // Pause recording action
        $('#recorder-pause').click(function () {
            BidAudition.showRecordBtn('#recorder-play');
            recorder().sendPause();

            return false; 
        });

        // Cancel recording action
        $('#record-again').click(function () {
            $('#record-status').addClass('hide');
            $('#record-start-msg').removeClass('hide');
            BidAudition.showRecordBtn('#recorder-record');
            recorder().sendStop();
            recorder().sendCancel();

            return false; 
        });

        // Save recording action
        $('#recorder-save').click(function () {
            recorder().sendSave();
            $('#bid-audio-audition').slideUp('fast', function () {
                recorder().sendSave();
                $('#bid-audio-uploading').slideDown('fast', function () {
                    
                });
            })
            return false; 
        });      
  
    },
    
    setupRecorder: function ()
    {
        var self = this;
        var params = BidAudition.params;
        
        /*
         * Setup recorder
         */
        var flashvars = {};

        flashvars.myFilename=params.recordAudioName;
        flashvars.myServer=params.domainBase;
        flashvars.myHandler=params.recordHandler;
        flashvars.timeLimit="30";
        flashvars.showLink="Y";
        flashvars.hideFlash="true";
        flashvars.bitRate="128";
        flashvars.licensekey=params.licenseKey;

        var parameters = {
            wmode:"transparent"
        };
        var attributes = {};
        attributes.id="recorder";
        attributes.name="recorder";
        
        swfobject.embedSWF(params.barebonesSwf + "?ID="+Math.random()*100, "recorder-container", "300", "165", "11.2", params.expressInstallSwf, flashvars, parameters, attributes);
    },
            
    showRecordBtn: function (id) {
        $('.btn-record', $('#bid-audio-audition')).addClass('hide');
        $(id).removeClass('hide');
    }
}
 
var recMessageArray = new Array()
recMessageArray[0] = "entering demo mode.";
recMessageArray[1] = "ready to go!";
recMessageArray[2] = "recording";
recMessageArray[3] = "stopped recording";
recMessageArray[4] = "stopped playing";
recMessageArray[5] = "ready!!";
recMessageArray[6] = "playing";
recMessageArray[7] = "starting to save";
recMessageArray[8] = "hmm. nothing to save";
recMessageArray[9] = "truncating the file to 10 seconds";
recMessageArray[10] = "Attaching to bid....";
recMessageArray[11] = "...";
recMessageArray[12] = 'Error while attaching to bid <a href="" id="record-again">Record again</a>';
recMessageArray[13] = "Encode complete";
recMessageArray[14] = "Attaching to bid....";
recMessageArray[15] = "Attached to bid";
recMessageArray[16] = "record pause";
recMessageArray[17] = "play pause";
recMessageArray[18] = "mic not ready";
recMessageArray[19] = "mic ready";

function recorder() {
    recorderId = 'recorder';
    return document[recorderId];
}

var lastStatus = null;
function recorderMessage(x) 
{
    // If last status is recording, then it changes to ready
    // Display play button
    if (lastStatus == 2 && x == 5) {
        $('#recorder-stop').addClass('hide');
        $('#recorder-pause').removeClass('hide');
        $('#record-actions, #record-again').removeClass('hide');
        recorder().sendStop();
        recorder().sendPlay();
        $("#recorder-meter-bar").removeClass('recording').addClass('playing');
        $("#recorder-meter-bar").css('width', 0);
    }
    console.log(x + ' ' + recMessageArray[x]);
    if (x == 19) {
        $('#recorder').css('height', '0px');
    }
    // Stop playing
    if (x == 4) {
        BidAudition.showRecordBtn('#recorder-play');
        $("#recorder-meter-bar").removeClass('playing');
        $("#recorder-meter-bar").css('width', 0);
    }
        
    // If encoding to mp3
    if (x == 10 || x == 12 || x == 13 || x == 14 || x == 15) {
        $('#bid-audio-message').text(recMessageArray[x]);
    }
    // If encoding error
    if (x == 12) {
            
    }
    // If upload completed
    if (x == 15) 
    {
        setTimeout(function () {
            $form = $('#bid-form');
            $('.hidden-audio', $form).remove();
            $form.append('<input class="hidden-audio" type="hidden" name="audio_file" value="'+params.recordAudioName+'" />');
            $form.append('<input class="hidden-audio" type="hidden" name="audio_title" value="Audition" />');
            
            // Get audio upload preview
            $audioUploadPreview = $('.audio-upload-preview', $('#bid-upload-audio-container'));
                
            // Update audio track
            $('.track-title', $audioUploadPreview).text('Recorded Audition');
            $('.track-play a', $audioUploadPreview).attr('href', params.uploadAudioTmp + "?f=" + params.recordAudioName + '&' + Math.floor(+new Date() / 1000)+'.mp3');
            $('.track-play', $audioUploadPreview).removeClass('ui360').addClass('ui360');
            threeSixtyPlayer.init();
            
            $('#bid-audio-uploading').slideUp('fast', function () {
                $('#audition-options').slideDown();
                $audioUploadPreview.slideDown();
            });
                
        }, 500);
    }
        
    lastStatus = x;
}

var recordMeter = -1;
function recorderMeter(x){
    recordMeter = x;
    if (x > 0) {
        $('#recorder').css('height', '0px');
    }
    
    if ($("#recorder-meter-bar").hasClass('recording')) {
        x = parseInt(x) * 10;
        x = x + 'px';
    }
    else {
        x = x + '%';
    }
    $("#recorder-meter-bar").css('width', x);
}

function recorderTime(x){
    $("#recorder-time").html(x);
}