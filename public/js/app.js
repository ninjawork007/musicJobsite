window.onload = function() {
    // right-click
    // document.addEventListener("contextmenu", function(e){
    //   e.preventDefault();
    // }, false);
    document.addEventListener("keydown", function(e) {
    //document.onkeydown = function(e) {
      // "I" key
      if (e.ctrlKey && e.shiftKey && e.keyCode == 73) {
        disabledEvent(e);
      }
      // "J" key
      if (e.ctrlKey && e.shiftKey && e.keyCode == 74) {
        disabledEvent(e);
      }
      // "S" key + macOS
      if (e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
        disabledEvent(e);
      }
      // "U" key
      if (e.ctrlKey && e.keyCode == 85) {
        disabledEvent(e);
      }
      // "F12" key
      if (event.keyCode == 123) {
        disabledEvent(e);
      }
    }, false);
    function disabledEvent(e){
      if (e.stopPropagation){
        e.stopPropagation();
      } else if (window.event){
        window.event.cancelBubble = true;
      }
      e.preventDefault();
      return false;
    }

    $('#buttonTable2csv').click(function() {
        var titles = [];
        var data = [];

        /*
         * Get the table headers, this will be CSV headers
         * The count of headers will be CSV string separator
         */
        $('#table2csv th').each(function() {
            titles.push($(this).text());
        });

        /*
         * Get the actual data, this will contain all the data, in 1 array
         */
        $('#table2csv td').each(function() {
            data.push($(this).text());
        });

        /*
         * Convert our data to CSV string
         */
        var CSVString = prepCSVRow(titles, titles.length, '');
        CSVString = prepCSVRow(data, titles.length, CSVString);

        /*
         * Make CSV downloadable
         */
        var downloadLink = document.createElement("a");
        var blob = new Blob(["\ufeff", CSVString]);
        var url = URL.createObjectURL(blob);
        downloadLink.href = url;
        downloadLink.download = "revenue.csv";

        /*
         * Actually download CSV
         */
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    });
    $('.buttonRowTable2csv').click(function() {
        var titles = [];
        var data = [];

        /*
         * Get the table headers, this will be CSV headers
         * The count of headers will be CSV string separator
         */
        $('#table2csv th').each(function() {
            titles.push($(this).text());
        });

        /*
         * Get the actual data, this will contain all the data, in 1 array
         */
        $(this).parent().parent().find('td').each(function() {
            data.push($(this).text());
        });

        /*
         * Convert our data to CSV string
         */
        var CSVString = prepCSVRow(titles, titles.length, '');
        CSVString = prepCSVRow(data, titles.length, CSVString);

        /*
         * Make CSV downloadable
         */
        var downloadLink = document.createElement("a");
        var blob = new Blob(["\ufeff", CSVString]);
        var url = URL.createObjectURL(blob);
        downloadLink.href = url;
        downloadLink.download = "revenue.csv";

        /*
         * Actually download CSV
         */
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    });

    /*
 * Convert data array to CSV string
 * @param arr {Array} - the actual data
 * @param columnCount {Number} - the amount to split the data into columns
 * @param initial {String} - initial string to append to CSV string
 * return {String} - ready CSV string
 */
    function prepCSVRow(arr, columnCount, initial) {
        var row = ''; // this will hold data
        var delimeter = ','; // data slice separator, in excel it's `;`, in usual CSv it's `,`
        var newLine = '\r\n'; // newline separator for CSV row

        /*
         * Convert [1,2,3,4] into [[1,2], [3,4]] while count is 2
         * @param _arr {Array} - the actual array to split
         * @param _count {Number} - the amount to split
         * return {Array} - splitted array
         */
        function splitArray(_arr, _count) {
            var splitted = [];
            var result = [];
            _arr.forEach(function(item, idx) {
                if ((idx + 1) % _count === 0) {
                    splitted.push(item);
                    result.push(splitted);
                    splitted = [];
                } else {
                    splitted.push(item);
                }
            });
            return result;
        }
        var plainArr = splitArray(arr, columnCount);
        // don't know how to explain this
        // you just have to like follow the code
        // and you understand, it's pretty simple
        // it converts `['a', 'b', 'c']` to `a,b,c` string
        plainArr.forEach(function(arrItem) {
            arrItem.forEach(function(item, idx) {
                row += item + ((idx + 1) === arrItem.length ? '' : delimeter);
            });
            row += newLine;
        });
        return initial + row;
    }
  };



App = {
    successTimeout: null,
    dataPingInterval: null,
    dataPingTimer: 30000,

    init: function () {

        function addScrollClass() {
            var scrollWidth = window.innerWidth - document.documentElement.clientWidth;

            if (scrollWidth) {
                document.documentElement.style.setProperty('--scrollbar-width', scrollWidth + 'px');
            }

            if (Math.max($("body").height(), $('#wrap').outerHeight() + $('#footer').outerHeight()) > $(window).height()) {
                $('body').addClass('has-scrollbar');
            } else {
                $('body').addClass('no-scrollbar');
            }
        }

        addScrollClass();
        $(window).on('show.bs.modal click', addScrollClass);

        $(window).on('hidden.bs.modal', function() {
            $('body').removeClass('has-scrollbar');
            $('body').removeClass('no-scrollbar');
        });

        $('.mobile-select-tabs, .nav.nav-tabs').click(function(e) {
            var $self = $(this);
            var opened = $self.hasClass('opened');

            if ($(window).width() <= 480 && !opened) {
                e.stopPropagation();
                e.preventDefault();
            }

            if (opened) {
                $self.removeClass('opened');
            } else {
                $self.addClass('opened');
            }
        });

        $('.percent-slider').slider({
            'min' : 0,
            'max' : 100,
            'value' : 0,
            'tooltip' : 'hide',
            'selection' : 'after'
        });
        $(".percent-slider").on('slideStop slide', function(slideEvt) {
            $(this).parent().siblings('.royalty-num').children('span').html(slideEvt.value);
        });
        jQuery.extend(Tipped.Skins, {
          'profile-nav' : {
            border: { size: 4, color: '#14b9d6' },
            background: '#14b9d6',
            radius: { size: 4, position: 'border' },
            shadow: true
          }
        });
        jQuery.extend(Tipped.Skins, {
          'dark-vskin' : {
            background: { color: '#18242b', opacity: .95 },
            radius: { size: 4, position: 'border' },
            border: { size: 0 },
            shadow: false,
            fadeIn: 0,
            fadeOut: 0,
            radius: 4,
            showOn: 'click',
          }
        });
        jQuery.extend(Tipped.Skins, {
          'dark-vskin-pro-stump' : {
            background: { color: '#18242b', opacity: .95 },
            radius: { size: 4, position: 'border' },
            border: { size: 0 },
            shadow: false,
            fadeIn: 0,
            fadeOut: 0,
            radius: 4,
          }
        });

        jQuery.extend(Tipped.Skins, {
          'light-vskin' : {
            background: { color: '#18242b', opacity: 1 },
            radius: { size: 4, position: 'border' },
            border: { size: 0 },
            shadow: true,
            fadeIn: 0,
            fadeOut: 0,
            radius: 4,
            offset: {y: 3}
          }
        });

        Tipped.create("#profile-dropdown-toggle", $('#profile-dropdown').html(), {
          skin: 'profile-nav',
          hook: 'bottommiddle'
        });

        Tipped.create("#notify-toggle", $('#notify-dropdown').html(), {
          skin: 'light-vskin',
          hook: 'bottommiddle',
          showOn: 'click',
          hideOn: 'click-outside',
            onShow: function () {
                $(".customScroll").mCustomScrollbar({
                    theme: "dark",
                });
                $('.notification').removeClass('unread');
                App.notificationClickEvents();

                var numUnread = parseInt($('.num-unread', $('.notification')).text());
                if (numUnread > 0) {
                    $('.num-unread', $('.notification')).text(0);
                }
            }
        });

        $('.notification a').on('click', function (e) {
            e.preventDefault();
        });

        $('.notification.unread a').on('click', function () {
            var url = $(this).data('url');
            $.ajax({
                url: url
            });
        });

        $('body').on('click', '#close-app-message', function(e) {

            e.preventDefault();
            var href = $(this).attr('href');
            $.ajax({
                type: "GET",
                url: href,
                dataType: 'json',
                success: function(data)
                {
                    if (data.success && data.newMessage) {
                        $('#app-message').replaceWith(data.newMessage);
                    } else {
                        $('#app-message').remove();
                    }
                }
            });
        });

        $('.activity-link').on('click', function(e) {
            e.preventDefault();
            var href = $(this).attr('href');
            var parentElement = $(this).parent();

            $.ajax({
                type: "GET",
                url: href,
                dataType: 'json',
                success: function(data)
                {
                    if (data.success) {
                        $('.activity-filter li').removeClass("active");
                        parentElement.addClass("active");
                        $('#vocalizr-activity').html(data.html);
                    }
                }
            });
        });

        $('body').on('click', '.attach-dropbox', function (e) {
            e.preventDefault();

            var filelist = $('#asset-filelist');
            if ($(this).data('listid')) {
                filelist = $('#' + $(this).data('listid'));
            }

            var options = options = {
                // Required. Called when a user selects an item in the Chooser.
                success: function (files) {
                    $.each(files, function(i, file) {
                            filelist.append(
                                    '<div class="new-asset"><i class="fa fa-dropbox"></i> <a href="' + file.link + '" target="_blank" class="exclude">' +
                                    file.name + ' (' + plupload.formatSize(file.bytes) + ')</a> <i class="fa fa-times remove-db-file remove-asset"></i>' +
                                    '<input type="hidden" name="dropbox_file_name[]" value="' + file.name + '">'  +
                                    '<input type="hidden" name="dropbox_file_link[]" value="' + file.link + '">'  +
                                    '<input type="hidden" name="dropbox_file_size[]" value="' + file.bytes + '">'  +
                            '</div>');
                    });
                    MessageCenter.setHeights();

                    $('#submit-assets').removeClass('hide');
                },
                linkType: "preview", // or "direct"
                multiselect: true,
                extensions: ['.mp3', '.zip'],
            };
            Dropbox.choose(options);
        });

        $('body').on('click', '.remove-db-file', function (e) {
            $(this).parent().remove();
            if ($('.new-asset').length == 0) {
                $('#submit-assets').addClass('hide');
            }
        });

        $('.tooltip-help-element').each(function() {
            var selector = '#' + $(this).data('tooltip-id');
            Tipped.create(this, $(selector)[0]);
        });

        $('#form_publish_type_0').on('ifChecked', function(e) {
            $('#publish-private-gig').hide(200, function() {
                $('#publish-public-gig').show(250);
            })
        });
        $('#form_publish_type_1').on('ifChecked', function(e) {
            $('#publish-public-gig').hide(200, function() {
                $('#publish-private-gig').show(250);
            })
        });

        $('#project_lyrics_needed_1').on('ifChecked', function(e) {
            $('.lyrics-input').slideDown();
        });

        $('#project_lyrics_needed_0').on('ifChecked', function(e) {
            $('.lyrics-input').slideUp('fast', function () {
                $('.lyrics-input textarea').val('');
            });
        });

        $('#form_project_type').on('ifChecked', '#form_project_type_0', function(e) {
            $('.budget-wrap').hide();
            $('.budget-wrap').removeClass('hidden');
            $('.collab-wrap').fadeOut('fast', function () {
                $('.budget-wrap').fadeIn('fast');
            });
        });
        $('#form_project_type').on('ifChecked', '#form_project_type_1', function(e) {
            $('.budget-wrap').fadeOut('fast', function () {
                $('.collab-wrap').removeClass('hidden');
                $('.collab-wrap').fadeIn('fast');
            });
        });

        $('#create-marketplace-item-form').on('ifChecked', '#form_is_auction', function(e) {
            $('#create-marketplace-item-form .auction-fields').slideDown('fast');
        });
        $('#create-marketplace-item-form').on('ifUnchecked', '#form_is_auction', function(e) {
            $('#create-marketplace-item-form .auction-fields').slideUp('fast');
        });

        $('#create-marketplace-item-form').on('keyup', '#marketplace_item_royalty_master', function() {
            if ($(this).val() > 0) {
                $('#marketplace_item_royalty_publishing').prop('disabled', true);
                $('#marketplace_item_royalty_mechanical').prop('disabled', true);
                $('#marketplace_item_royalty_performance').prop('disabled', true);
            } else {
                $('#marketplace_item_royalty_publishing').prop('disabled', false);
                $('#marketplace_item_royalty_mechanical').prop('disabled', false);
                $('#marketplace_item_royalty_performance').prop('disabled', false);
            }
        });

        $('#create-marketplace-item-form').on('keyup', '#marketplace_item_royalty_publishing, #marketplace_item_royalty_mechanical, #marketplace_item_royalty_performance', function() {
            if ($(this).val() > 0) {
                $('#marketplace_item_royalty_master').prop('disabled', true);
            } else {
                $('#marketplace_item_royalty_master').prop('disabled', false);
            }
        });

        /*
        $('#project_project_type').on('ifChecked', '#project_project_type_0', function(e) {
            $('.budget-wrap').hide();
            $('.budget-wrap').removeClass('hidden');
            $('.budget-wrap').slideDown(250);
        });
        $('#project_project_type').on('ifChecked', '#project_project_type_1', function(e) {
            $('.budget-wrap').slideUp(250);
        });
        */

        $('.invite').each(function() {
            var eId = $(this).data('id');
            Tipped.create(this, $('#gigs-dropdown-' + eId).html(), {
                skin: 'dark-vskin',
                hook: 'bottommiddle',
                maxWidth: 250,
                onShow: function () {
                    $('.gigs-tip-list a').unbind('click');
                    $('.gigs-tip-list a').on('click', function (e) {
                       App.gigInvite(e, $(this));
                    });
                }
            });
        });



        $('.form-royalty-type-group i').each(function() {
            var eId = $(this).data('id');
            Tipped.create(this, $('#royalty-tip').html(), {
                skin: 'dark-vskin',
                hook: 'bottommiddle',
                maxWidth: 250
            });
        });

        $('.tip, .vocalizr-certified, .vocalizr-certified-stamp, .vocalizr-certified-stamp-clipped, .vocalizr-certified-required, .icon-royalty-mechanical, .icon-royalty-performance, .badge-pro').each(function() {
            var text = null;

            if ($(this).hasClass('badge-pro')) {
                if (!$(this).parent().hasClass('btn')) {
                    text = 'Member has PRO Subscription';
                }
            } else {
                text = $(this).data('text');
            }

            if (text) {
                Tipped.create(this, text, {
                    skin: 'dark-vskin',
                    hook: 'bottommiddle',
                    maxWidth: 250
                });
            }

        });

        $('.svg-stamp-pro').each(function() {
            var text = $(this).data('text');

            if (text) {
                Tipped.create(this, text, {
                    skin: 'dark-vskin-pro-stump',
                    hook: 'bottommiddle',
                    maxWidth: 250
                });
            }

        });

        soundManager.setup({
          // path to directory containing SM2 SWF
          url: '/swf/',
        });

        /*
         * Modals
         */
        $('body').on('click', '[data-toggle="vmodal"]', function (e) {
            e.preventDefault();
            if (!$(this).hasClass('allow-audio-play')) {
                soundManager.stopAll();
            }
            App.showLoading();
            var hr = $.get($(this).attr('href'), function (data) {
                $('#loading').hide();
                $('#vocalizrModal').html(data);
                $('#vocalizrModal').modal('show');
            });
            hr.fail(function (data) {
                $('#loading').hide();
                $('#vocalizrModal').html(data.responseText);
                $('#vocalizrModal').modal('show');
            });
        });

        $(window).on('resize', function ()
        {
            if ($('.track-waveform .position').length > 0) {
                $('.playlist.track-waveform.small').each(function (index, el) {
                    var width = $('.waveform-main').innerWidth();
                    $('.position img', el).css('width', width);
                });
            }
        });

        /**
         * Tabs
         */
        if ($('.nav-tabs').length > 0) {
             if (location.hash.length > 0) {
                 var hash, selector;
                 hash = selector = location.hash;
                 if (hash.charAt(0) !== '#') {
                     selector = '#' + hash;
                 }
                 $('.nav-tabs a[href="'+hash+'"]').tab('show');
                 $(selector).addClass('active');
             }
             else {
                try {
                    var $firstTab = $('.nav-tabs a[data-toggle]').first();
                     $firstTab.tab('show');
                     if ($firstTab.data('toggle')) {
                         console.log($firstTab.attr('href'));
                         // $($('.nav-tabs a').first().attr('href')).addClass('active');
                     }
                } catch(err) {
                    // do nothing
                }
             }
        }

        /* Custom select / tags */
        $('.select2').select2();

        $('.select2-budget').select2()
            .on("change", function(e) {
                if ($('.budget-desc').length > 0)
                {
                    $('.budget-desc div').hide();
                    $('#'+$(this).val()).fadeIn();

                    if ($(this).hasClass('budget-cost')) {
                        if ($(this).val() == "0") {
                            $('.custom-budget').slideDown();
                        }
                        else {
                            $('.custom-budget').slideUp();
                        }
                        //$(t)
                    }
                }
            }
        );

        $('.select2-project-type').select2()
            .on("change", function(e)
            {
                if ($('.budget-desc').length > 0)
                {
                    if ($(this).val() == "producer") {
                        $('.budget-desc').hide();
                        $('#'+$(this).val()).fadeIn();
                        $('.lyrics-wrap').hide();
                    }
                    else {
                        $('.budget-desc').show();
                        $('.lyrics-wrap').show();
                    }
                }
            }
        );

        $('.tag-select').select2({
            tags: true,
            multiple: true,
            initSelection : function (element, callback) {
                var data = [];
                $(element.val().split(",")).each(function () {
                        if (this != '') {
                            data.push({id: this, text: this});
                        }
                    }
                );
                callback(data);
            },
        });

        $('.tag-input').select2({
            tags: [],
            multiple: false,
            maximumSelectionSize: 5,
            initSelection : function (element, callback) {
                var data = [];
                $(element.val().split(",")).each(function () {
                        if (this != '') {
                            data.push({id: this, text: this});
                        }
                    }
                );
                callback(data);
            },
            formatNoMatches: function (term) {
                return 'Copy your social link here and press enter'
            },
            formatSelectionTooBig: function (term) {
                return 'You can only specify 5 artists'
            }
        });

        /* Star ratings */
        $('.star-disabled').raty({
            path: '/images',
            readOnly: true,
            width: 93,
            hints: ['Bad', 'Poor', 'OK', 'Good', 'Great!'],
            score: function() {
              return $(this).attr('data-score');
            }
        });
        $('.star-enabled').raty({
            path: '/images',
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


        /* Custom checkboxes */
        $('input').iCheck({
            checkboxClass: 'icheckbox_polaris',
            radioClass: 'iradio_polaris'
        });

        /*
         * Date Picker
         */
        $('.datepicker').datepicker();

        /*
         * Hide alerts
         */
        setTimeout("$('.alert').slideUp();", 5000);

        /*
         * User Tag Voting
         */
        $('.vote-tag a').on('click', function (e) {
            App.userTagVote(e, $(this));
        });

        $('#reviews-tab-toggle').on('click', function (e) {
           e.preventDefault();
           $('.nav-tabs a[href="#reviews"]').tab('show');
        });

        /**
         * Review rating details
         */
        $('.review-rating').each(function() {
            var eId = $(this).data('id');
            Tipped.create(this, $('#review-rating-' + eId).html(), {
                skin: 'dark-vskin',
                hook: 'bottommiddle'
            });
        });

        App.loadingTop = parseInt($('#loading').css('top'));
        $(window).scroll(function()
        {
            var scrollTop = $(this).scrollTop();
            $('#loading').css('top', (scrollTop + App.loadingTop) + 'px');
            $('#successNotification').css('top', (scrollTop + App.loadingTop) + 'px');

         });

        /**
         * Profile tasks
         */
        $('.profile-task.incomplete .task-header').on('click', function() {
            var targetElement = $(this);
            targetElement.next().slideToggle();
        });

        $('#sounds-like-form button').on('click', function() {
            $('.error', $('#sounds-like-form')).remove();
            App.showLoading();

            $.ajax({
                   type: "POST",
                   url: $('#sounds-like-form').attr('action'),
                   data: $('#sounds-like-form').serialize(),
                   dataType: 'json',
                   success: function(data)
                   {
                       $('#loading').hide();
                       if (data.success) {
                           $('#sounds-like-form').parent().slideUp(function() {
                               $('#sounds-like-form').parent().remove();
                           });
                           $('#sounds-like-form').parent().parent().removeClass('incomplete');
                       }
                       else {
                           $('#sounds-like-form').children().first().children('.field-label').first().after($('<span class="error"> ' + data.message + '</span>'));
                       }
                   },
                   error: function () {
                       $('#loading').hide();
                   }
                 });
        });

        $('#user-city-form button').on('click', function() {
            $('.error', $('#user-city-form')).remove();
            App.showLoading();
            $.ajax({
                   type: "POST",
                   url: $('#user-city-form').attr('action'),
                   data: $('#user-city-form').serialize(),
                   dataType: 'json',
                   success: function(data)
                   {
                       $('#loading').hide();
                       if (data.success) {
                           $('#user-city-form').parent().slideUp(function() {
                               $('#user-city-form').parent().remove();
                           });
                           $('#user-city-form').parent().parent().removeClass('incomplete');
                       }
                       else {
                           $('#user-city-form').children().first().children('.field-label').first().after($(' <span class="error"> ' + data.message + '</span>'));
                       }
                   },
                   error: function () {
                       $('#loading').hide();
                   }
                 });
        });

        $('#user-genres-form button').on('click', function() {
            $('.error', $('#user-genres-form')).remove();
            App.showLoading();
            $.ajax({
                   type: "POST",
                   url: $('#user-genres-form').attr('action'),
                   data: $('#user-genres-form').serialize(),
                   dataType: 'json',
                   success: function(data)
                   {
                       $('#loading').hide();
                       if (data.success) {
                           $('#user-genres-form').parent().slideUp(function() {
                               $('#user-genres-form').parent().remove();
                           });
                           $('#user-genres-form').parent().parent().removeClass('incomplete');
                       }
                       else {
                           $('#user-genres-form').children().first().children('.field-label').first().after($('<span class="error"> ' + data.message + '</span>'));
                       }
                   },
                   error: function () {
                        $('#loading').hide();
                   }
                 });
        });

        $('body').on('click', '.user-audio-like .btn', App.likeAudio);
        $('body').on('submit', '#frm-connect', App.submitConnect);
        $('body').on('click', '.btn-connect-cancel', App.cancelConnect);
        $('body').on('click', '.btn-connect-accept', App.acceptConnect);
        $('body').on('click', '.btn-connect-accept-notify', App.acceptConnectNotify);
        $('body').on('click', '.btn-connect-ignore-notify', App.ignoreConnectNotify);

        $('body').on('click', '.btn-app-certify', function (e) {
            e.preventDefault();
            $el = $(this);
            $el.attr('disabled', true);

            App.showLoading();

            $.getJSON($(this).attr('href'), function (data) {
               $('#loading').hide();
               if (data.certified) {
                   $el.text('REMOVE CERTIFY');
               }
               else {
                   $el.text('CERTIFY');
               }

               $el.removeAttr('disabled');
            });
        });

        $(document).on('vocalizr.modal.bid-highlight', function(e, custom) {
            $('#bid-highlight').clone(true).appendTo($('#vocalizrModal').empty()).css('display', 'block');
            $('#vocalizrModal #bid-highlight .options').attr('data-uuid', custom.bid_uuid);
            $('#vocalizrModal').modal('show');
            $('#vocalizrModal input').iCheck({
                radioClass: 'iradio_polaris'
            });
        });

        $('body').on('ifChecked', '#vocalizrModal .highlight-bid-modal input', function () {
            var $option = $(this).parents('.option');
            var isFree = $option.hasClass('free');

            if (isFree) {
                $('#vocalizrModal .highlight-bid-modal .btn.paid').removeClass('btn-primary').addClass('btn-secondary').text('Not this time!');
            } else {
                $('#vocalizrModal .highlight-bid-modal .btn.paid').removeClass('btn-secondary').addClass('btn-primary').text('Pay & Highlight Bid Now');
            }
        });

        $('body').on('click', '#vocalizrModal .highlight-bid-modal button', function () {
            if (stripe === undefined) {
                throw 'Stripe is not properly initialized.';
            }

            var $btn = $(this);

            var $modal = $('#vocalizrModal');

            var option = $('#vocalizrModal .highlight-bid-modal input[name=bid_option]:checked').val() || 0;

            if ($btn.attr('type') !== 'submit') {
                option = 4;
            }

            option = parseInt(option);
            var $options = $('#vocalizrModal .highlight-bid-modal .options');

            $.ajax({
                url: $options.data('href'),
                data: {
                    uuid: $options.data('uuid'),
                    option: option,
                },
                success: function (responseData) {
                    if (!responseData.success) {
                        alert('An error occurred.');
                    } else if (option == 4) {
                        $modal.modal('hide');
                    } else {
                        stripe.redirectToCheckout({ sessionId: responseData.ssid });
                    }
                },
                error: function () {
                    alert('An error occurred.');
                }
            });
        });

        $('body').on('click', '.btn-show-bid-highlight-modal', function (e) {
            e.preventDefault();
            $(document).trigger('vocalizr.modal.bid-highlight', {bid_uuid: $(this).data('uuid')});
        });

        /*
         *
         * AJAX Events
         */
        $('.member-favourite').on('click', function(e) {
            App.handleMemberFav(e, $(this));
        });
        $('.member-block').on('click', function(e) {
            App.handleMemberBlock(e, $(this));
        });

        // notification updates
        if ($('#user-avatar').length) {
            App.dataPingInterval = setInterval("App.dataPing()", App.dataPingTimer);
        }

        // Scalable player in assets:

        var $assetElems = $('.asset-audio');

        if ($assetElems.length > 0) {
            App.initPlayerResize($assetElems);
        }

        function closeMenu($menu) {
            $menu.removeClass('in');
            $('body').removeClass('modal-open');
        }

        var menuHeightInterval = null;
        $('[data-toggle=mobile-menu]').on('click', function (event) {
            event.preventDefault();

            var $menu = $('.mobile-menu.collapse');
            var $menuContainer = $menu.find('.mobile-menu-container');

            if ($menu.hasClass('in')) {
                clearInterval(menuHeightInterval);
                closeMenu($menu);
            } else {
                var lastHeight;
                clearInterval(menuHeightInterval);
                menuHeightInterval = setInterval(function () {
                    var winHeight = $(window).height();
                    if (lastHeight === winHeight) {
                        return;
                    }
                    lastHeight = winHeight;
                    $menuContainer.css('height', winHeight);
                    $menuContainer.css('min-height', winHeight);
                    $menuContainer.css('max-height', winHeight);
                }, 100);
                $menu.addClass('in');
                $('body').addClass('modal-open');
                $menu.find('a, button').on('click', function () {
                    closeMenu($menu);
                });
            }

        });

        $('body').on('click', '#resend-email', function(e) {
            e.preventDefault();
            var $button = $(this);
            $.ajax({
                dataType: "json",
                type: "GET",
                url:  $button.attr('href'),
                success: function(data)
                {
                    $button.closest('.modal').html(data.html);
                }
            });
        });

        function closeDropdowns() {
            $('.vocalizr-dropdown').removeClass('in');
            $('body').unbind('click', bodyClickHandler);
        }

        var bodyClickHandler = function (event) {
            var $target = $(event.target);
            var isCloseBtn = false;
            if ($target.attr('class')) {
                isCloseBtn = $target.attr('class').includes('close-btn');
            }

            if ($target.parents('.vocalizr-dropdown').length > 0 && !isCloseBtn && $target.parents('.close-btn').length === 0) {
                return;
            }
            closeDropdowns();
        };

        $('[data-toggle=vocalizr-dropdown]').on('click', function (event) {
            var $toggle = $(this);
            var $subject = $(event.target);
            var $dropdown = $toggle.find('.vocalizr-dropdown');

            if ($subject.parents('.vocalizr-dropdown').length > 0 || $dropdown.hasClass('in')) {
                return;
            }
            event.preventDefault();
            event.stopPropagation();
            $dropdown.addClass('in');
            $('body').bind("click", bodyClickHandler);
            $dropdown.find('a, button').on('click', function () {
                closeDropdowns();
            });
        })
    },

    initPlayerResize: function($assetElems) {
        var assetsStyles = window.getComputedStyle($assetElems[0]);
        var playerOriginalWidth = assetsStyles.getPropertyValue('--player-width');
        playerOriginalWidth = parseFloat(playerOriginalWidth.substr(0, playerOriginalWidth.length - 2));
        var playerOriginalHeight = assetsStyles.getPropertyValue('--player-height');
        playerOriginalHeight = parseFloat(playerOriginalHeight.substr(0, playerOriginalHeight.length -2));

        var playerAspect = playerOriginalWidth / playerOriginalHeight;

        // Resize player when it appears on page.
        $assetElems.show('slide',function(){
            App.resizePlayer($(this), playerAspect);
        });
        // Resize players on tab change (due to strange bug with "on show" listener when element is in not in current tab).
        $(document).on('shown.bs.tab', 'a[data-toggle="tab"]', function (e) {
            App.resizePlayers($assetElems, playerAspect);
        });
        // Resize players on window resizing.
        $(window).resize(function () {
            App.resizePlayers($assetElems, playerAspect);
        })
    },

    resizePlayers: function($playerObjects, playerAspect) {
        $playerObjects.each(function () {
            var $elem = $(this);
            App.resizePlayer($elem, playerAspect);
        });
    },

    resizePlayer: function($elem, playerAspect) {
        var $parent = $($elem.parent());
        var playerWidth = $parent.width() - 50;
        var playerHeight = playerWidth / playerAspect;

        if (playerHeight < 1 || playerWidth < 1) {
            console.error('Null player width or height. Ignore resize.');
            return;
        }
        $elem[0].style.setProperty('--player-width', playerWidth + 'px');
        $elem[0].style.setProperty('--player-height', playerHeight + 'px');
    },

    dataPing: function(options) {
        var url = $('.navbar-brand').attr('href') + 'dataPing', scroll = true;
        if (options && options.messaging) {
            // Get current page
            var page = $('.threads-list').data('page');
            url += '/msg/' + options.uuid + "?page=" + page;
            if (options.scroll !== null && (typeof(options.scroll) !== 'undefined')) {
                scroll = options.scroll;
            }
        }
        $.ajax({
            dataType: "json",
            type: "GET",
            url:  url,
            success: function(data)
            {
                if (data.success) {
                    $('.message-alert .num-unread').html(data.numUnreadMsg);
                    if (data.numUnreadMsg > 0) {
                        $('.message-alert').addClass('unread');
                        $('.message-alert .num-unread').show();
                        $('.threads-header .num-unread').removeClass('label-default');
                        $('.threads-header .num-unread').addClass('label-red');
                    } else {
                        $('.message-alert').removeClass('unread');
                        $('.message-alert .num-unread').hide();
                        $('.threads-header .num-unread').removeClass('label-red');
                        $('.threads-header .num-unread').addClass('label-default');
                    }
                    $('.threads-header .num-unread').html(data.numUnreadMsg);
                    if (data.extra == 'msg') {
                        $('.threads-wrap .threads-list').html(data.threadsHtml);
                        $('.messages-wrap .label.new-message').fadeOut(function() {
                            $(this).remove();
                        });
                        if (data.messages) {
                            $('.messages-wrap .messages').append(data.messages);
                            if (scroll === true) {
                                $('.messages-wrap').mCustomScrollbar("scrollTo", "bottom");
                            }
                        }
                        if (data.threadOpen === false) {
                            if ($('.chat-closed').length == 0) {
                                $('.messages-header .actions').empty();
                                $('<div class="chat-closed"><div>This gig has been awarded</div><div>Chat is now closed</div></div>').appendTo($('.messages-header .actions'));
                                $('.messages-reply-wrap').remove();
                                MessageCenter.setHeights();
                            }
                        }
                    }
                    if ($('.threads-wrap').length) {
                        $('.threads-wrap').mCustomScrollbar({
                            theme: 'dark',
                        });
                    }
                }
            }
        });

    },

    notificationClickEvents: function () {
      $('.notify-dd li').off('click');
      $('.notify-dd li').on('click', function (e) {
          if ($(this).data('url')) {
              location.href = $(this).data('url');
          }
      });
    },

    submitConnect: function (e) {
        e.preventDefault();

        $('#cue').html(''); // reset error

        $('#vocalizrModal').modal('hide');
        App.showLoading();

        var username = $('#connect-user').val();
        var connectLink = $('a[data-connect="'+username+'"].btn-connect');
        var cancelLink = $('a[data-connect="'+username+'"].btn-connect-cancel');
        connectLink.attr('disabled', true);

        var jqxhr = $.post($(this).attr('action'), $(this).serialize(), function () {
            connectLink.addClass('hide');
            cancelLink.removeClass('hide');

            $('#loading').hide();
            App.showSuccess('Invite sent');
        });
        jqxhr.fail(function (data) {
            $('#loading').hide();
            $('#vocalizrModal').html(data.responseText);
            $('#vocalizrModal').modal('show');
        });
        jqxhr.always(function () {
            connectLink.attr('disabled', false);
        });
    },

    cancelConnect: function (e) {
        e.preventDefault();

        App.showLoading();

        var cancelLink = $(this);
        var username = cancelLink.data('connect');
        var connectLink = $('a[data-connect="'+username+'"].btn-connect');
        cancelLink.attr('disabled', true);

        var jqxhr = $.get($(this).attr('href'), function () {
            connectLink.removeClass('hide');
            cancelLink.addClass('hide');
            $('#loading').hide();
        });
        jqxhr.fail(function (data) {
            $('#loading').hide();
            $('#vocalizrModal').html(data.responseText);
            $('#vocalizrModal').modal('show');
        });
        jqxhr.always(function () {
            cancelLink.attr('disabled', false);
        });
    },

    acceptConnect: function (e) {
        e.preventDefault();

        App.showLoading();

        var acceptLink = $(this);
        var username = acceptLink.data('connect');

        var messageLink = $('a[data-connect="'+username+'"].btn-message');
        acceptLink.attr('disabled', true);

        var jqxhr = $.get($(this).attr('href'), function (data) {
            messageLink.removeClass('hide');
            acceptLink.addClass('hide');
            $('#loading').hide();
            App.showSuccess(data.message);

            // Find notification if it exists and hide
            var notifyLink = $('a[data-connect="'+username+'"].btn-connect-accept-notify');
            notifyLink.parents('.connect_invite').remove();
            Tipped.refresh('#notify-toggle');

        });
        jqxhr.fail(function (data) {
            $('#loading').hide();
            $('#vocalizrModal').html(data.responseText);
            $('#vocalizrModal').modal('show');
        });
        jqxhr.always(function () {
            acceptLink.attr('disabled', false);
        });
    },

    acceptConnectNotify: function (e) {
        e.preventDefault();

        App.showLoading();

        var acceptLink = $(this);
        var username = acceptLink.data('connect');
        acceptLink.parent().slideUp(50, function () {
            Tipped.refresh('#notify-toggle');
        });

        // If there are connect buttons anywhere else on the site, we need to change them
        // depending on this result
        var acceptLinks = $('a[data-connect="'+username+'"].btn-connect-accept');
        var messageLinks = $('a[data-connect="'+username+'"].btn-message');
        acceptLinks.attr('disabled', true);

        var jqxhr = $.get($(this).attr('href'), function (data) {
            $('#loading').hide();
            // Display / hide other buttons related to connect on site
            messageLinks.removeClass('hide');
            acceptLinks.addClass('hide');
            // Hide the notification
            acceptLink.parents('.connect_invite').slideUp(50, function () {
                Tipped.refresh('#notify-toggle');
            });
            App.showSuccess(data.message);
        });
        jqxhr.fail(function (data) {
            acceptLink.parent().slideDown();
            $('#loading').hide();
            $('#vocalizrModal').html(data.responseText);
            $('#vocalizrModal').modal('show');
        });
        jqxhr.always(function () {
            acceptLink.attr('disabled', false);
        });
    },

    ignoreConnectNotify: function (e) {
        e.preventDefault();

        App.showLoading();

        var ignoreLink = $(this);
        $(ignoreLink).prev().attr('disabled', true);

        var username = ignoreLink.data('connect');
        ignoreLink.parent().slideUp(50, function () {
            Tipped.refresh('#notify-toggle');
        });

        var jqxhr = $.get($(this).attr('href'), function (data) {
            $('#loading').hide();
            // Hide the notification
            ignoreLink.parents('.connect_invite').slideUp(50, function () {
                Tipped.refresh('#notify-toggle');
            });
        });
        jqxhr.fail(function (data) {
            ignoreLink.parent().slideDown();
            $('#loading').hide();
            $('#vocalizrModal').html(data.responseText);
            $('#vocalizrModal').modal('show');
        });
        jqxhr.always(function () {
            ignoreLink.attr('disabled', false);
        });
    },

    userTagVote: function (e, obj) {
        e.preventDefault();
        var $el = $(obj);
        if ($el.hasClass('disabled')) {
            return;
        }
        var url = '';
        var data = {};
        var isVoted = $el.hasClass('voted');
        if (isVoted && $el[0].hasAttribute('data-disable-href')) {
            url = $el.data('disable-href');
        } else {
            url = $el.attr('href');
        }

        if ($el[0].hasAttribute('data-type') || $el[0].hasAttribute('data-id')) {
            data = {type: $el.data('type'), id: $el.data('id')};
        }

        var $counter = $el;

        if ($el[0].hasAttribute('data-counter')) {
            console.log($el.data('counter'));
            $counter = $($el.data('counter'));
            console.log($counter);
        }

        App.showLoading();
        $.post(url, data, function (data) {
            $('#loading').hide();
            if (data.error !== undefined) {
                App.showModal({title: 'Please login', content: data.error});
                return;
            }
            $el.toggleClass('voted');
            var count = parseInt($counter.first().text());
            if ($el.hasClass('voted')) {
                $counter.text(count + 1);
                App.showSuccess('Vote added');
            } else {
                $counter.text(count - 1);
                App.showSuccess('Vote removed');
            }
        });
    },

    /**
     * Handle member favorite
     */
    handleMemberFav: function (e, obj) {
        e.preventDefault();
        App.showLoading();
        $.getJSON($(obj).attr('href'), function (data)
        {
           $('#loading').hide('fast');
           if (typeof data.success !== 'undefined') {
               if (data.success == "added") {
                    $('span', obj).html('REMOVE FAVOURITE');
                    App.showSuccess('Favourite added');
               }
               else if (data.success == "removed") {
                    $('span', obj).html('ADD TO FAVOURITES');
                    App.showSuccess('Favourite removed');
               }
               return;
           }
           if (typeof data.error !== 'undefined') {
               App.showModal({title: 'Ooops!', content: data.error});
           }

        });
    },

    /**
     * Handle member block
     */
    handleMemberBlock: function (e, obj) {
        e.preventDefault();
        App.showLoading();
        $.getJSON($(obj).attr('href'), function (data)
        {
           $('#loading').hide('fast');
           if (typeof data.success !== 'undefined') {
               if (data.success == "added") {
                    $('span', obj).html('UNBLOCK');
                    App.showSuccess('Member Blocked');
               }
               else if (data.success == "removed") {
                    $('span', obj).html('BLOCK');
                    App.showSuccess('Member Unblocked');
               }
               return;
           }
           if (typeof data.error !== 'undefined') {
               App.showModal({title: 'Ooops!', content: data.error});
           }

        });
    },


    showSuccess: function (message)
    {
        $('#successNotification').hide();
        clearTimeout(App.successTimeout);
        $('#successNotification').text(message);
        $('#successNotification').css("left", ($(document).width()-$('#successNotification').width())/2);
        $('#loading').hide();
        $('#successNotification').fadeIn('fast');
        App.successTimeout = setTimeout("$('#successNotification').fadeOut('slow');", 2000);
    },

    showLoading: function () {
        var scrollTop = $(window).scrollTop();
        $('#loading').css('top', (scrollTop + App.loadingTop) + 'px');

        $('#successNotification').hide();
        $('#loading').fadeIn('fast');
    },

    showProAccountPrompt: function() {
        App.showModal({title: 'Upgrade to Vocalizr PRO', content: "<a href='user/upgrade'>Click here</a> to upgrade to Vocalizr PRO."});
    },

    gigInvite: function (e, obj) {
        e.preventDefault();
        App.showLoading();

        // if they clicked create gig then go there
        if ($(obj).hasClass('gig-invite-create')) {
            location.href = $(obj).attr('href');
            return;
        }

        $.getJSON($(obj).attr('href'), function (data)
        {
           if (typeof data.success !== 'undefined') {
               text = $(obj).text();
               $(obj).html('<i class="fa fa-check"></i> ' + text);
               App.showSuccess('Invite sent');
               return;
           }
           if (typeof data.error !== 'undefined' && data.error == 'already-invited') {
               App.showSuccess('You have already invited this user to the gig!');
               return;
           }
           if (typeof data.error !== 'undefined') {
               App.showModal({title: 'Ooops!', content: data.error});
           }
           Tipped.refresh('.invite');
        });
    },

    showModal: function (data) {
       var body = '<div class="modal-dialog">' +
                '<div class="modal-content">' +
                    '<div class="modal-header">' +
                        '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
                        '<h4 class="modal-title">' + data.title + '</h4>' +
                    '</div>' +
                    '<div class="modal-body"><div class="modal-row">' + data.content + '</div></div>' +
                    '</div>';
       $('#vocalizrModal').html(body);
       $('#vocalizrModal').modal('show');
    },

    getCookie: function (name, defaultValue) {
        // Split cookie string and get all individual name=value pairs in an array
        var cookieArr = document.cookie.split(";");

        // Loop through the array elements
        for(var i = 0; i < cookieArr.length; i++) {
            var cookiePair = cookieArr[i].split("=");

            /* Removing whitespace at the beginning of the cookie name
            and compare it with the given string */
            if(name == cookiePair[0].trim()) {
                // Decode the cookie value and return
                return decodeURIComponent(cookiePair[1]);
            }
        }

        return defaultValue;
    },

    setCookie: function (name, value, maxAge) {
        var cookie = name + "=" + encodeURIComponent(value);
        if (typeof maxAge === "number") {
            cookie += "; max-age=" + maxAge;
        }
        document.cookie = cookie;
    },

    /**********************
     * SoundCloud functions
     */
     bindScFunctions: function ()
     {
         $('.sc-fetch-tracks').unbind('click');
         $('.sc-fetch-tracks').on('click', function (e) { App.scFetchTracks(e, $(this)); });

         $('.sc-select-track').unbind('click');
         $('.sc-select-track').on('click', function (e) { App.scSelectTrack(e, $(this)); });

         $('.sc-select-track-task').unbind('click');
         $('.sc-select-track-task').on('click', function (e) { App.scSelectTrackTask(e, $(this)); });

         threeSixtyPlayer.init();
     },
     scFetchTracks: function (e, obj)
     {
         e.preventDefault();
         // If already fetching, don't attempt to fetch again
         if ($(obj).hasClass('loading')) {
             return false;
         }

         $(obj).addClass('loading');
         $(obj).html('<i class="fa fa-cloud-download"></i> Loading...');
         $.get($(obj).data('url'), function (data) {
             $(window).focus();
             $('#soundcloud-tracks .modal-row').html(data);
             $(obj).html('<i class="fa fa-cloud-download"></i> Fetch Tracks');
             App.bindScFunctions();
         });

     },
     scSelectTrack: function (e, obj) {
        e.preventDefault();
        $('#vocalizrModal').modal('hide');
        var $parent = $(obj).parent();

        var trackTitle = $('.track-title', $parent).text();
        var href = $('.track-play a', $parent).data('href');
        var scId = $(obj).data('id');
        var $badge = $('.badge-soundcloud', $parent);

        $('#user-audio-title').val(trackTitle);

        // Upload audio preview on page
        $audioPrev = $('.audio-upload-preview');
        $audioPrev.slideUp();
        $('.track-play a', $audioPrev).attr('href', href + '&r=' + Math.random());
        $('.track-play a', $audioPrev).attr('type', 'audio/mp3');
        $('.track-play', $audioPrev).removeClass('ui360').addClass('ui360');
        $('.track-title', $audioPrev).text(trackTitle);

        // Add / remove soundcloud badge
        $('.badge-soundcloud', $audioPrev).remove();
        $('.badge-featured', $audioPrev).after($badge.clone());

        // Insert hidden track id to save with form
        $('.hidden-audio', $audioPrev.parent().closest('form')).remove();
        $audioPrev.prepend('<input class="hidden-audio sc-track-id" type="hidden" name="sc_track_id" value="'+scId+'">');

        $audioPrev.slideDown('fast', function () {
            threeSixtyPlayer.init();
        });
     },

     scSelectTrackTask: function (e, obj) {
        e.preventDefault();
        var parent = $(obj).parent();
        var trackTitle = $('.track-title', parent).text();
        var scId = $(obj).data('id');

        form = $('#task-user-audio-form');
        form.prepend('<input class="hidden-audio sc-track-id" type="hidden" name="sc_track_id" value="'+scId+'">');
        $('#task-upload-btns').addClass('hide');
        $('#task-upload-audio-status').html('<img src="/images/ajax-loader.gif"> &nbsp; <span class="white-highlight">Saving ' + trackTitle + "</span>");
        $('#task-upload-audio-status').removeClass('hide');
        form.submit();

        $('#vocalizrModal').modal('hide');
     },

     recordPlay: function (obj)
     {
        if (obj.data('user')) {
            if (!obj.hasClass('played')) {
                obj.addClass('played');
                count = parseInt($('.' + obj.data('user') + '-count').first().text());
                $('.' + obj.data('user') + '-count').text(count + 1);
                $.getJSON('/audio/' + obj.data('user') + '/record');
            }
            ga('send', 'event', 'Audio', 'Play User', obj.data('user'));
        }
        if (obj.data('project')) {
            ga('send', 'event', 'Audio', 'Play Project', obj.data('project'));
        }
        if (obj.data('bid')) {
            ga('send', 'event', 'Audio', 'Play Bid', obj.data('bid'));
        }
     },

     likeAudio: function (e) {

         e.preventDefault();
         var parentEl = $(this).parent();

         var parent = $('.' + parentEl.parent().attr('id'));
         if (parent.length == 0) {
             parent = $('#' + parentEl.parent().attr('id'));
         }

         var totalLikes = parseInt($('.total-likes', parentEl).text());
         var status = $(this).hasClass('btn-like');

         $.getJSON($(this).attr('href'));

         if (status) {
             $('.btn-like', parent).addClass('hide');
             $('.btn-unlike', parent).removeClass('hide');
             totalLikes++;

         }
         else {
             $('.btn-like', parent).removeClass('hide');
             $('.btn-unlike', parent).addClass('hide');
             totalLikes--;
         }
         $('.total-likes', parent).text(totalLikes);
         if (totalLikes == 1) {
            $('.member-text', parent).text('member');
         }
         else {
             $('.member-text', parent).text('members');
         }
     },
}

App.init();

Message = {
    initCompose: function() {
        var msgContainer = $('.message-compose');
        msgContainer.on('click', '.message-send', function(e) {
            e.preventDefault();

            var form = $('#compose-message-form');
            if ($("#compose-message-form .form-control").val().trim() !== '') {
                $.ajax({
                    dataType: "json",
                    type: "POST",
                    url:  form.attr('action'),
                    data: form.serialize(),
                    success: function(data)
                    {
                        if (data.success) {
                            $('.modal-header .close').click();
                            App.showSuccess('Message successfully sent');
                            $('#compose-message').hide();
                            $('#discuss-gig').attr('href', data.url).removeClass('hide');
                        }
                    }
                });
            }
        });
    }
}
MessageCenter = {
    setHeights: function() {
        var allowedHeight = $(window).height() - 383;
        var minHeight = 350;
        if (allowedHeight < minHeight) {
            $('.messages-container').height(minHeight);
            if ($('.messages-reply-wrap').length > 0) {
                $('.messages-wrap').height(minHeight - $('.messages-reply-wrap').outerHeight());
            } else {
                $('.messages-wrap').height(minHeight);
            }
            $('.threads-wrap').height(minHeight);
        } else {
            $('.messages-container').height(allowedHeight);
            if ($('.messages-reply-wrap').length > 0) {
                $('.messages-wrap').height(allowedHeight - $('.messages-reply-wrap').outerHeight());
            } else {
                $('.messages-wrap').height(allowedHeight);
            }
            $('.threads-wrap').height(allowedHeight);
        }

    },

    init: function() {
        // set the height of the threads wrap and messages based on the container height
        MessageCenter.setHeights();

        $('body').on('click', '.btn-thread-load', function (e) {
            e.preventDefault();
            $(this).html('<i class="fa fa-spinner fa-spin"></i> LOADING');
            $(this).attr('disabled', true);
            var activeThread = $('.messages-header').data('thread');
            clearInterval(App.dataPingInterval);
            var page = $('.threads-list').data('page');
            $('.threads-list').data('page', page + 1);
            if (activeThread) {
                App.dataPing({'messaging': true, 'uuid':  activeThread});
                App.dataPingInterval = setInterval("App.dataPing({'messaging': true, 'uuid': '" + activeThread + "'})", App.dataPingTimer);
            } else {
                App.dataPing({'messaging': true});
                App.dataPingInterval = setInterval("App.dataPing({'messaging': true})", App.dataPingTimer);
            }
        });

        $(".customScroll").mCustomScrollbar({
            theme: "dark",
        });

        var activeThread = $('.messages-header').data('thread');
        clearInterval(App.dataPingInterval);
        if (activeThread) {
            App.dataPingInterval = setInterval(function () {
                App.dataPing({messaging: true, uuid: activeThread, scroll: false})
            }, App.dataPingTimer);
        } else {
            App.dataPingInterval = setInterval("App.dataPing({'messaging': true})", App.dataPingTimer);
        }

        $('.message-center').show();
        $(window).resize(function() {
            MessageCenter.setHeights();
        });

        $('.messages-wrap').mCustomScrollbar("scrollTo", "bottom", {
            timeout: 500,
            scrollInertia: 0,
        });

        $('.threads-wrap').on('click', '.thread', function() {
            if ($('.new-asset').length > 0) {
                if (!confirm("You have uploaded files, if you continue you will lose them. Please send before continuing")) {
                    return false;
                }
            }
            var url = $(this).data('msgs-url');
            var el = $(this);
            App.showLoading();
            soundManager.stopAll();

            $.ajax({
                dataType: "json",
                type: "GET",
                url:  url,
                success: function(data)
                {
                    $('#loading').hide();
                    if (data.success) {
                        $('.messages-container').html(data.html);
                        MessageCenter.setHeights();
                        $('.threads-wrap .thread').removeClass('selected');
                        $('#mt-'+ el.data('thread')).addClass('selected');
                        clearInterval(App.dataPingInterval);
                        App.dataPingInterval = setInterval("App.dataPing({'messaging': true, 'uuid': '" + $('.messages-header').data('thread') + "', 'scroll': false})", App.dataPingTimer);
                        $('.messages-wrap, .message-file-list').mCustomScrollbar({
                            theme: "dark",
                        });
                        $('.messages-wrap').mCustomScrollbar("scrollTo", "bottom", {
                            timeout: 200
                        });
                        threeSixtyPlayer.init();
                        AssetUploader.init('upload-file-container', 'upload-files-btn');
                    }
                }
            });
        });

        $('.messages-container').on('click', '.message-remove-file', function (e) {
            e.preventDefault();

            if (confirm('Are you sure you want to delete this file?')) {
                $.getJSON($(this).attr('href'));

                $(this).parent().remove();
            }
        });

        $('.messages-container').on('click', '.message-reply', function(e) {
            e.preventDefault();
            var btn = $(this);
            var form = $('#reply-message-form');
            if ( form.children('.form-control').val().trim() == '') {
                alert("Please enter a message");
                return;
            }
            btn.attr('disabled', true);
            $.ajax({
                dataType: "json",
                type: "POST",
                url:  form.attr('action'),
                data: form.serialize(),
                success: function(data)
                {
                    btn.removeAttr('disabled');
                    if (data.success) {
                        App.showSuccess('Message successfully sent');
                        var lastMessage = $('.messages-wrap .message').last();
                        $('.messages-wrap .messages').append(data.html);
                        $('.messages-reply-wrap textarea').val('');

                        $('.messages-wrap').mCustomScrollbar("scrollTo", "bottom");
                        $('#asset-filelist .new-asset').remove();
                        MessageCenter.setHeights();
                    }
                }
            });
        });

        $('.message-center').on('click', '.mark-all-read', function(e) {
            e.preventDefault();

            var url = $(this).attr("href");
            $.ajax({
                dataType: "json",
                type: "GET",
                url:  url
            });

            $(".threads-wrap .unread").html("0");
            $(".threads-wrap .unread").removeClass("label-red");
            $(".threads-wrap .unread").addClass("label-default");

            $(".threads-container .num-unread").html("0");
            $(".threads-container .num-unread").removeClass("label-red");
            $(".threads-container .num-unread").addClass("label-default");

            $(".message-alert .num-unread").html("0");
            $(".message-alert .num-unread").hide();
            $(".message-alert").removeClass("unread");
        });
    }
};
