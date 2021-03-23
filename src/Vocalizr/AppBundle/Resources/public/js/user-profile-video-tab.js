VocalizrAppUserProfileVideoTab = function ($) {

    var self = this;

    this.videoFrames     = [];
    this.videoCounteiner = null;

    this.init = function (withSorting, sortPath) {

        self.sortPath = sortPath;

        self.videoCounteiner = $('[data-role="video-container"]');

        var array = [];

        $('[data-role="video-player"]').each(function (i, obj) {
            array.push(obj);
        });

        self.initPlyr(array);

        $('[data-role="add-more-videos"]').on('click', function () {
            var path = $(this).data('path'),
                id = $(this).data('id'),
                limit = $(this).data('limit'),
                edit = $(this).data('edit')
                ;

            $.ajax({
                url: path,
                data: {
                    id: id,
                    offset: self.videoFrames.length,
                    limit: limit,
                    edit: edit
                },
                success: function (data) {
                    if (data.count < limit) {
                        $('[data-role="add-more-videos"]').hide();
                    }

                    self.videoCounteiner.append(data.html);

                    if (data.count > 0) {
                        self.checkAndAddVideos();
                        self.initSorting()
                    }
                }
            })
        });

        if(withSorting) {
            self.initSorting()
        }
    };

    this.initPlyr = function (array) {

        for(var i = 0; i < array.length; i++) {
            new Plyr(array[i], {
                clickToPlay: false,
                debug: false
            });
            self.videoFrames.push($(array[i]).data('id'));
        }
    };

    this.checkAndAddVideos = function () {

        var array = [];

        $('[data-role="video-player"]').each(function(index, obj){

            for(var i = 0; i < self.videoFrames.length; i++ ){
                if(self.videoFrames[i] == $(obj).data('id')) {
                    return false;
                }
            }
            array.push(obj);
        });

        self.initPlyr(array);
    };

    this.initSorting = function(){

        $(".video-row").sortable({
            connectWith: '.sort-video',
            handle: ".sort-video-icon",
            placeholder: "sort-video-placeholder",
            helper: 'clone',
            revert: true,
            tolerance: "pointer",
            start: function(e, ui){
                ui.placeholder.width(ui.item.width());
                ui.placeholder.height(ui.item.height());
                ui.placeholder.addClass(ui.item.attr("class"));
            }
        }).on('sortstop', function (e, ui) {
                var item = ui.item;
                var content = $(item).find('[data-role="video-content"]');

                content.html($(item).find('[data-role="meta-container"]').data('meta'));

                new Plyr($(content).find('div'), {
                    clickToPlay: false,
                    debug: false
                });
        }).on('sortupdate', function () {

            var data = [];

            $('[data-role="video-content"]').each(function(index, obj){

                data.push({id: $(obj).data('id'), position: index});
            });

            $.ajax({
                url: self.sortPath,
                method: 'POST',
                data: {data: JSON.stringify(data)},
                success: function(data){

                }
            })
        });
    }
};