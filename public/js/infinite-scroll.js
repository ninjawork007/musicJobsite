function VocalizrScroll($, path){

    var wait       = false;
    var noResults  = false;
    var queryCount = 0;

    var activity = $('#vocalizr-activity');

    $(window).on('scroll', function vocalizrActivity() {

        if($(document).height() - $(this).innerHeight() - $(this).scrollTop() < 200) {

            if(!wait && !noResults && queryCount < 4) {
                wait = true;
                activity.append('<div id="js-activity-loading">LOADING...</div>');

                queryCount++;
                $.ajax({
                    url: path,
                    type: "POST",
                    dataType: 'json',
                    data: {first: $('.activity-list-item').length},
                    success: function(data){
                        wait = false;
                        removeScrollSpinner();
                        activity.append(data.html);

                        if (data.html === "") {
                            noResults = true;
                        }

                        if (queryCount >= 4 && !noResults) {
                            $(window).off('scroll', vocalizrActivity);

                            loadMore(path)
                        }
                    }
                });
            }
        }
    });

}

function removeScrollSpinner() {
    $('#js-activity-loading').remove();
}

function loadMore(path) {

    var activity = $('#vocalizr-activity');

    var noResults = false;

    activity.append('<a class="load-more btn btn-default">Load More</a>');
    $('a.load-more').on('click', function () {

        if (!noResults) {
            $.ajax({
                url: path,
                type: "POST",
                dataType: 'json',
                data: {first: $('.activity-list-item').length},
                success: function (data) {
                    removeScrollSpinner();
                    activity.append(data.html);

                    if (data.html === "") {
                        noResults = true;
                        $('a.load-more').remove();
                        activity.append('<div style="color: #ceccd9">No more activities yet..</div>')
                    } else {
                        activity.append($('a.load-more'));
                    }

                }
            });
        }
    })
}