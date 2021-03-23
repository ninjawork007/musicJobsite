function VocalizrAppUserMoreReview($){

    var self = this;

    this.init = function(){

        var page = 1;

        $('[data-role="load-move-review-button"]').on('click', function(){

            var button = $(this);

            $.ajax({
                url:  button.data('path'),
                data: {
                    page: ++page,
                    id: button.data('id'),
                    type: button.data('type')
                },
                success: function(data){
                    if(data.success) {
                        $('[data-role="review-container"]').append(data.html);

                        if(data.hide){
                            button.hide();
                        }

                        self.setDefaultStarOption();
                    }
                }
            })

        });
    };

    this.setDefaultStarOption = function(){

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

        $('.review-rating').each(function() {
            var eId = $(this).data('id');
            Tipped.create(this, $('#review-rating-' + eId).html(), {
                skin: 'dark-vskin',
                hook: 'bottommiddle'
            });
        });
    }
}