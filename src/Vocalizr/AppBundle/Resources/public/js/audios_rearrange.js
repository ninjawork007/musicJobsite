TracksManager = {
    init: function(tracks, path)
    {
        tracks.dragging = true;
        let newOrderArray = {};
        let orderArray = {};
        $('button.rearrange').on('click', function(e) {
            e.preventDefault();

            if (tracks.dragging) {
                $('#tracks-container').children().each(function(index, elem) {
                    orderArray[$(elem).attr('data-id')] = index;
                });

            } else {
                $('#tracks-container').children().each(function(index, elem) {
                    newOrderArray[$(elem).attr('data-id')] = index;
                });

                $.ajax({
                    url: path,
                    method: "POST",
                    data: { new_order: newOrderArray , order: orderArray }
                })
            }

            $(this).hasClass('active') ? $(this).removeClass('active') : $(this).addClass('active');
            tracks.dragging = !tracks.dragging;
        });
    }
};
