
Studio = {
    
    url: null,
    refreshInterval: 30000,
    refreshFeed: null,
    
    init: function (url) 
    {
        Studio.url = url;
        
        Studio.refreshFeed = setTimeout(function() {
                Studio.getUpdatedFeed();
        }, Studio.refreshInterval); 
    
        $('#comment-form').on('submit', function (e) {
            Studio.commentFormHandler(e, $(this));
        });
        
        $('#release-payment-toggle').on('click', function (e) {
            e.preventDefault();
            $('a[href=#payment]').tab('show');
        });
        
        $('.lyrics-versions .version').on('click', function (e) {
            $version = $(this);
            $.getJSON($(this).data('href'), function (data) {
                $('#current-lyrics').val(data.lyrics);
                $clone = $('#active-arrow').clone();
                
                $('#active-arrow').remove();
                $('.lyrics-versions .version').removeClass('current');
                $version.prepend($clone);
                $version.addClass('current');
               
            });
        });
        
        $('#dispute-show').on('click', function (e) {
            e.preventDefault();
            $('#dispute-container').slideToggle();
        });
        
        $('#project_dispute_amount').on('blur', function () {
            $(this).toNumber();
            if ($(this).val() < 0) {
                $(this).val('');
                return;
            }
            $(this).formatCurrency({symbol: '', roundToDecimalPlace: 0});
        });
        $('#project_dispute_amount').formatCurrency({symbol: '', roundToDecimalPlace: 0});
        
        /* Star ratings */
        $('.star-rating').raty({ 
            path: '/images',
            click: function () {
                Studio.calcAverageRating();
            },
            scoreName: function () {
                if ($(this).data('score-name')) {
                    return $(this).data('score-name');
                }
                else {
                    return 'score';
                }
            },
            hints: ['Bad', 'Poor', 'OK', 'Good', 'Great!'],
            score: function() {
              return $(this).attr('data-score');
            }
        });
        Studio.calcAverageRating();
        
    
        /**
         * Submit review
         */
        $('#review-form').on('submit', function (e) {
            Studio.reviewSubmit(e, $(this));
        });
    
    },
            
    rebind: function ()
    {
        $('.feed-lyrics').unbind('click');
        $('.feed-lyrics').click(function () {
            $('#gig-tabs a[href="#lyrics"]').tab('show');
            return false;
        });
    },
    
    commentFormHandler: function(e, eventObj)
    {
        e.preventDefault();
        
        if ($('#comment-form textarea').val() == "") {
            App.showModal({title: 'Error', content: "Comment field cannot be empty"});
            return;
        }
        
        var lastFeedId = 0;
        if ($('#feed .feed-item').length > 0) {
            lastFeedId = $('#feed .feed-item').first().data('id');
        }

        clearTimeout(Studio.refreshFeed);

        $('#lastFeedId').val(lastFeedId);
        
        $.post($(this).attr('action'), $(eventObj).serialize(), function(data) {
            if (data.length > 0) {
                $('#feed').prepend(data);
                Studio.rebind();
            }
            $('#comment-form textarea').val('');
            $('#comment-filelist').html('');
            $('.hidden-audio', $('#comment-form')).remove();
            $('.audio-upload-preview').slideUp();
            soundManager.stopAll();
        });

        Studio.refreshFeed = setTimeout(function() {
            Studio.getUpdatedFeed();
        }, Studio.refreshInterval);
    },

    getUpdatedFeed: function () 
    {
        var lastFeedId = 0;
        if ($('#feed .feed-item').length > 0) {
            lastFeedId = $('#feed .feed-item').first().data('id');
        }
        $.get(Studio.url + '/feed', {lastFeedId: lastFeedId}, function (data) {
            if (data.length > 0) {
                $('#feed').prepend(data);
                // Update read items, only if window is in focus
                if (windowFocus) {
                    updateReadItems();
                }
                Studio.rebind();
            }
            Studio.refreshFeed = setTimeout(function() {
                Studio.getUpdatedFeed();
            }, Studio.refreshInterval);
        });
    },
    
    updateReadItems: function () 
    {
        // Update any new feed items as read
       if ($('#feed .new-item').length) {
           var data = {};
           data['feed_items'] = {};

           $('#feed .new-item').each(function (index, el) {
               data['feed_items'][index] = $(el).data('id');
           });
           $('#feed .new-item').removeClass('new-item');
           // Post to update feed items as read
           $.post(Studio.url, $.param(data), function () {
               $('#feed .highlight').removeClass('highlight');
           });
       }
    },
            
    calcAverageRating: function () {
       var total = 0;
       $('#star-ratings input').each(function (index, el) {
           if (el.value != '') {
               total += parseInt(el.value);
           }
       });
       var avg = parseFloat(total / $('#star-ratings input').length);
       $('#average-rating').text(avg.toFixed(1) + '/5.0');
    },
            
    reviewSubmit: function (e, obj)
    {
        $('#rating.error').slideUp();
        
        // Make sure they have rated all areas
        var valid = true;
        $('.star-rating input', obj).each(function (index, input) {
            if ($(input).val() == '') {
                error = "Please give star rating to all categories below";
                valid = false;
            }
        });
        
        if (valid) {
            if ($.trim($('#review-content').val()) == "") {
                valid = false;
                error = "Please provide additional feedback in the textarea below";
                $('#review-content').focus();
            }
        }
        
        if (valid) {
            var content = $.trim($('#review-content').val());
            if (content.length < 10) {
                valid = false;
                error = "Please give more detail in your feedback";
                $('#review-content').focus();
            }
        }
        
        if (!valid) {
            e.preventDefault();
            $('#rating-error').text(error);
            $('#rating-error').slideDown();
        }
    }
}