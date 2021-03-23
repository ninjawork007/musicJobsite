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
recMessageArray[10] = "Encoding to MP3";
recMessageArray[11] = "...";
recMessageArray[12] = "Encoding error";
recMessageArray[13] = "Encode complete";
recMessageArray[14] = "Uploading...";
recMessageArray[15] = "Upload complete";
recMessageArray[16] = "record pause";
recMessageArray[17] = "play pause";
recMessageArray[18] = "mic not ready";
recMessageArray[19] = "mic ready";
    
var lastStatus = null;
function recorderMessage(x) 
{
    // If last status is recording, then it changes to ready
    // Display play button
    if (lastStatus == 2 && x == 5) {
        $('#recorder-stop').addClass('hide');
        $('#recorder-pause').removeClass('hide');
        $('#recorder-status-btns').removeClass('hide');
        recorder().sendStop();
        recorder().sendPlay();
    }
    console.log(x + ' ' + recMessageArray[x]);
    if (x == 19) {
        $('#recorder').css('height', '0px');
    }
    // Stop playing
    if (x == 4) {
        showRecordBtn('#recorder-play');
    }
        
    // If encoding to mp3
    if (x == 10 || x == 13 || x == 14 || x == 15) {
        $('#recorder-upload-status span').text(recMessageArray[x]);
    }
    // If encoding error
    if (x == 12) {
            
    }
    // If upload completed
    if (x == 15) 
    {
        setTimeout(function () {
            $('#record-audio').addClass('hide');
            $('#bid-audio-actions').removeClass('hide');
                
            // Update audio track
            $('#bid-audio .ui360 a').attr('href', uploadAudioTmp + "?f=" + recordAudioName + '&' + Math.floor(+new Date() / 1000));
            $('#bid-audio-file').val(recordAudioName);
            threeSixtyPlayer.init();
            $('#bid-audio').slideDown();
                
        }, 500);
    }
        
    lastStatus = x;
}
    
function showRecordBtn(id) {
    $('.record-action').addClass('hide');
    $(id).removeClass('hide');
}
    
var recordMeter = -1;
function recorderMeter(x){
    recordMeter = x;
    if (x > 0) {
        $('#recorder').css('height', '0px');
    }
    else {
        //console.log('whatttt');
        //$('#recorder').height(170);
    }
    $("#recorder-meter-bar").css('width', parseInt(x) * 2 + "px");
}
    
function recorderTime(x){
    $("#recorder-time").html(x);
}
    
function recorder() {
    recorderId = 'recorder';
    return document[recorderId];
/*
        if (navigator.appName.indexOf("Microsoft") != -1) {
            alert('test: ' + navigator.appName);
            return document[recorderId];
        } else {
            alert('test2: ' + navigator.appName);
            return document[recorderId];
        }
        */
}
    
$(function () 
{
    /*
         * Setup recorder
         */
    var flashvars = {};
        
    flashvars.myFilename=recordAudioName;
    flashvars.myServer=domainBase;
    flashvars.myHandler=recordHandler;
    flashvars.timeLimit="30";
    flashvars.showLink="Y";
    flashvars.hideFlash ="true";
    flashvars.bitRate="64";
    flashvars.licensekey=licenseKey;
        
    var parameters = {
        wmode:"transparent"
    };
    var attributes = {};
    attributes.id="recorder";
    attributes.name="recorder";
        
    // Embed audio recorder and display actions
    $('#record-trigger').click(function ()
    {
        $('#record-status').addClass('hide');
        $('#record-start-message').removeClass('hide');
        showRecordBtn('#recorder-record');
            
        $('#recorder-upload-status').addClass('hide');
        $('#recorder-meter-container').removeClass('hide');
        $('#recorder-time').removeClass('hide');
        $('#recorder-save').removeClass('hide');
           
        $('#bid-audio-actions').addClass('hide');
        resetBidAudio();
        $('#record-audio').removeClass('hide');
        swfobject.embedSWF(barebonesSwf + "?ID="+Math.random()*100,"recorderDIV","300","165","11.2", expressInstallSwf, flashvars, parameters, attributes);
        return false;
    });
        
    function resetBidAudio() {
        $('#bid-audio').slideUp();
        $('#bid-audio-file').val('');
    }
        
    $('#remove-track').click(function () {
        resetBidAudio();
    })
        
    // Record action
    $('#recorder-record').click(function () {
        showRecordBtn('#recorder-stop');
           
        $('#recorder-status-btns').addClass('hide');
           
        $('#record-status').removeClass('hide');
        $('#record-start-message').addClass('hide');
        recorder().sendRecord();
        return false; 
    });
        
    // Stop recording action
    $('#recorder-stop').click(function ()
    {      
        $('#recorder-status-btns').removeClass('hide');
        recorder().sendStop();
        recorder().sendPlay();
        return false; 
    });
        
    // Play recording action
    $('#recorder-play').click(function () {
        showRecordBtn('#recorder-pause');
        recorder().sendPlay();
        return false; 
    });
        
    // Pause recording action
    $('#recorder-pause').click(function () {
        showRecordBtn('#recorder-play');
        recorder().sendPause();
           
        return false; 
    });
        
    // Cancel recording action
    $('#recorder-cancel').click(function () {
        $('#record-status').addClass('hide');
        $('#record-start-message').removeClass('hide');
        showRecordBtn('#recorder-record');
        recorder().sendStop();
        recorder().sendCancel();
           
        return false; 
    });
        
    // Save recording action
    $('#recorder-save').click(function () {
        recorder().sendSave();
        $(this).addClass('hide'); // hide save button so they can't save again
        $('#recorder-meter-container').addClass('hide');
        $('#recorder-time').addClass('hide');
        $('#recorder-upload-status').removeClass('hide');
        return false; 
    });
        
    /**
         * Remove audio recorder
         */
    $('#reset-bid-audio').click(function () {
        $('#record-audio').addClass('hide');
        $('#bid-audio-actions').removeClass('hide');
        recorder().sendStop();
        recorder().sendCancel();
            
        showRecordBtn('#recorder-record');
        return false;
    });
        
        
    $('#bid-form').submit(function () {
        $('#bid-error').hide();
        
        if ($('#form-bid-amount').val() == '') {
            $('#bid-error').text('Please enter a bid amount');
            $('#bid-error').slideDown();
            $('#form-bid-amount').focus();
            return false;
        }
        if ($('#gig-terms').length > 0 && !$('#gig-terms').is(':checked')) {
            $('#bid-error').text('Please agree to gig terms');
            $('#bid-error').slideDown();
            return false;
        }
        return true;
    });

});
    