
Project = {
    
    init: function () 
    {
        $('#signature-form').on('submit', function () 
        {
            $('#signature .error').remove();
            if ($('#signature input').val() == "") {
                $('#signature label').append(' <span class="error">Required</span>');
                $('#signature input').focus();
                return false;
            }
            return true;
        });
    }
}

function createTips() {
    Tipped.create($('.btn-discuss'), 'Discuss', {
        skin: 'dark-vskin',
        hook: 'topmiddle',
        maxWidth: 250
    });
    Tipped.create($('.vote-count'), 'What is an upvote?', {
        skin: 'dark-vskin',
        hook: 'topmiddle',
        maxWidth: 250
    });
    Tipped.create($('.btn-hide-bid'), 'Hide', {
        skin: 'dark-vskin',
        hook: 'topmiddle',
        maxWidth: 250
    });
    Tipped.create($('.btn-unhide-bid'), 'Unhide', {
        skin: 'dark-vskin',
        hook: 'topmiddle',
        maxWidth: 250
    });
    Tipped.create($('.btn-shortlist'), 'Shortlist', {
        skin: 'dark-vskin',
        hook: 'topmiddle',
        maxWidth: 250
    });
    Tipped.create($('.btn-shortlist-unhide'), 'Remove from Shortlist', {
        skin: 'dark-vskin',
        hook: 'topmiddle',
        maxWidth: 250
    });
}
createTips();

$('body').on('click', '.btn-shortlist, .btn-shortlist-unhide', function (e)
{
    e.preventDefault();

    $el = $(this);
    $(this).attr('disabled', true);
    App.showLoading();
    
    $.getJSON($(this).attr('href'), function (data)
    {
        $('#loading').hide();
        if (data.error !== undefined) {
            App.showModal({title: 'Error', content: data.error});
            return false;
        }

        if (data.result)
        {// add to shortlist
            var $bidEl = $('.bid-'+$el.data('uuid'));
            $el.removeAttr('disabled');
            $('#shortlist .bid-' + $el.data('uuid')).remove();
            $el.html('<i class="fa fa-star" aria-hidden="true"></i>');
            $('.shortlist-' + $el.data('uuid')).removeClass('btn-shortlist').addClass('btn-shortlist-unhide');
            Tipped.remove($el);
            var $cloned = $bidEl.clone();
            $('#shortlist').prepend($cloned);
            Tipped.create($('.shortlist-' + $el.data('uuid')), 'Remove from Shortlist', {
                skin: 'dark-vskin',
                hook: 'topmiddle',
                maxWidth: 250
            });
            var $wrap = $cloned.find('.js-waveform-wrap');
            if ($wrap.length > 0) {
                $wrap.find('wave').remove();
                $wrap.find('audio').remove();
                waveforms.initWaveform($wrap[0]);
            }
        } else {// remove from shortlist
            $('.shortlist-' + $el.data('uuid')).removeClass('btn-shortlist-unhide').addClass('btn-shortlist');
            $('.shortlist-' + $el.data('uuid')).html('<i class="far fa-star" aria-hidden="true"></i>');
            $('#shortlist .bid-' + $el.data('uuid')).remove();
            $el.removeAttr('disabled');
        }
        createTips();


    });
});

$('body').on('click', '.btn-hide-bid, .btn-unhide-bid', function (e)
{
    e.preventDefault();

    $el = $(this);
    $(this).attr('disabled', true);
    App.showLoading();
    
    $.getJSON($(this).attr('href'), function (data)
    {
        $('#loading').hide();
        if (data.error !== undefined) {
            App.showModal({title: 'Error', content: data.error});
            return false;
        }
        var $bidEl = $('.bid-'+$el.data('uuid'));
        if ($bidEl.length > 1) {
            $bidEl = $bidEl.first();
        }
            
        if (!data.result)
        {
            $('.hide-' + $el.data('uuid')).removeClass('btn-hide-bid').addClass('btn-unhide-bid');
            $el.removeAttr('disabled');
            $('.btn-shortlist, .btn-shortlist-unhide', $bidEl).addClass('hide');
            $('.btn-shortlist, .btn-shortlist-unhide', $bidEl).html('<i class="far fa-star" aria-hidden="true"></i>');
            $('.btn-shortlist, .btn-shortlist-unhide', $bidEl).removeClass('btn-shortlist-unhide').addClass('btn-shortlist');
            $('#shortlist .bid-' + $el.data('uuid')).remove();
            
            $('#hidden').prepend($bidEl);
            $('.hide-' + $el.data('uuid')).html('<i class="fa fa-reply" aria-hidden="true"></i>');
        }
        else {       
            $('.hide-' + $el.data('uuid')).removeClass('btn-unhide-bid').addClass('btn-hide-bid');
            $('.hide-' + $el.data('uuid')).html('<i class="fa fa-times" aria-hidden="true"></i>');
            $('.btn-shortlist, .btn-shortlist-unhide', $bidEl).removeClass('hide');
            $('#all').prepend($bidEl);
            $el.removeAttr('disabled');
        }
        createTips();


    });
});

$('body').on('click', '.btn-upvote', function (e)
{
    e.preventDefault();
    $el = $(this);
    App.showLoading();
    var $bidEl = $('.bid-'+$el.data('uuid'));
        
    $('.btn-upvote', $bidEl).attr('disabled', true);
    $.getJSON($(this).attr('href'), function (data) {

        $('#loading').hide();
        if (data.error !== undefined) {
            App.showModal({title: 'Error', content: data.error});
            return false;
        }
        
        $('.btn-upvote', $bidEl).text("UPVOTED");
        
        var text = data.count + ' vote';
        if (data.count > 1) {
            text += "s";
        }
        $('.share-voted-bid', $bidEl).removeClass('hide');
        $('.vote-count-num', $bidEl).text(text);
        $('.vote-count .fa-arrow-up', $bidEl).addClass('bounce');
        setTimeout(function() { $('.vote-count .fa-arrow-up').removeClass('bounce'); }, 800);
        
        
    });
});


$('body').on('click', '.btn-more-bids', function (e)
{
    e.preventDefault();
    $el = $(this);
    App.showLoading();
    $el.attr('disabled', true);

    $.get($(this).attr('href'), function (data) {

        $('#loading').hide();
        $el.prev().remove();
        $el.remove();
        $('.bids-list').append(data);
       
        
    });
});

$('body').on('click', '.btn-agree-download', function (e) {
   $('.modal-header .close').click(); 
});

const $extendContest    = $('.edit-contest-panel');
const $extendContestBtn = $extendContest.find('.edit-contest-pay-button');
const $totalBox         = $extendContest.find('.total-box');

$extendContest.find('input[type=radio]').each(function (index, radio) {
    const $el = $(radio);
    $el.on('ifChecked', function (e) {
        $extendContestBtn.removeAttr('disabled');
        $extendContestBtn.data('days', $el.val());
        $totalBox.removeClass('hide');
        $totalBox.find('.total-value').text($el.data('cost'));
    });
});

$extendContestBtn.click(function () {
    if (stripe === undefined) {
        throw 'Stripe is not properly initialized.';
    }

    $extendContestBtn.prop('disabled', true);
    App.showLoading();

    $.ajax({
        url: $extendContestBtn.data('url'),
        data: {
            uuid: $extendContestBtn.data('uuid'),
            days: $extendContestBtn.data('days'),
        },
        success: function (data) {
            if (data.success) {
                stripe.redirectToCheckout({ sessionId: data.ssid })
            } else {
                // TODO: catch this and also fail callback.
            }
        },
        complete: function() {
            setTimeout(() => {
                $extendContestBtn.prop('disabled', false);
                App.hideLoading();
            }, 1000);
        }
    });
});