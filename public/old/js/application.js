$(document).ready(function() {
    $(".chzn-select").chosen({
        width: '250px'
    });
    $(".chzn-select-gig").chosen({
        width: '96%'
    });
    $(".chzn-select-search").chosen({
        width: '180px'
    });
    
    slideEvent = null
    $('.avatar-info-wrap').on('mouseenter', function() {
        slideEvent = $(this).children('.avatar').first().slideUp();
    });
    $('.avatar-info-wrap').on('mouseleave', function(e) {
        slideEvent.stop()
        e.stopPropagation();
        $(this).children('.avatar').first().show();
    });
    
    /** 
     * Tabs
     */
    $('.nav-tabs a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
    });
    
    if (location.hash.length > 0) {
        $('.nav-tabs a[href="'+location.hash+'"]').tab('show');
    }
    
    /**
     * Tag manager
     */
    $(".tagManager").tagsManager({
        CapitalizeFirstLetter: true,
        preventSubmitOnEnter: true
    });
    
    /**
     * Tooltips
     */
    $('.tip').tooltip();
    
    /**
     * Datepicker
     */
    $('.datepicker').datepicker();
    
    $('.dropdown-toggle').dropdown();  
    
    soundManager.setup({
      // even if HTML5 supports MP3, prefer flash so the visualization features can be used.
      preferFlash: true,
      useFlashBlock: true,
      useHighPerformance: true,
      bgColor: '#ffffff',
      debugMode: true,
      url: '/swf/',
      // hide initial flash of white on everything except firefox, IE 8 and Safari on Windoze
      wmode: 'transparent',
      flashVersion: 9,
    });
    
    $.extend(Tipped.Skins, {
        'custom-white' : $.extend(true, { }, Tipped.Skins.white, {
            // we're removing padding through CSS, but while ajax is loading we want the spinner to have padding
            spinner: {
                padding: 10
            }
        }) 
    });
    
    
    /**
     * Switch style
     *
     */
    $('.switch').each(function () {
        $(this).bootstrapSwitch('toggleState');
        if ($('input', this).attr("checked")) {
            $(this).bootstrapSwitch('setState', true);
        }
        else {
            $(this).bootstrapSwitch('setState', false);
        }
    });
    
    /**
     * On all modals, need to stop music on close
     */
    
    
    $('.invite-to-gig ul li a.invite').click(function () {
        var href = $(this).attr('href');
        var $el = $(this);
        $.getJSON(href, function (data) {
            // If successful
            if (data.success !== undefined) {
                alert('User has been invited');
                $el.dropdown('toggle')
                return;
            }
            // else error
            alert(data.error);
            
        });
        return false;
    });
    
});

function isNumber(str)
{
    var numberRegex = /^[+-]?\d+(\.\d+)?([eE][+-]?\d+)?$/;
    if(numberRegex.test(str)) {
       return true;
    }
    return false;
}

function getParameterByName(variable) {
       var query = window.location.search.substring(1);
       var vars = query.split("&");
       for (var i=0;i<vars.length;i++) {
               var pair = vars[i].split("=");
               if(pair[0] == variable){return pair[1];}
       }
       return(false);
}

