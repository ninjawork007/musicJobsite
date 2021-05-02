function VocalizrAppViewMoreConnections($){

    this.init = function() {
        var wait = false;

        $('[data-role="add-more-connections"]').on('click', function(){

            var $this                = $(this);
            var $connectionContainer = $('[data-role="user-connections-container"]');
            var connectionsCount     = parseInt($this.data('connections-count'));
            var viewedConnections    = $('[data-role="connection-item"]').length;
            var loadingHTML          = $this.data('loading-text');
            var normalHTML           = $this.html();

            if (!wait) {
                wait = true;

                $this.html(loadingHTML);
                $this.prop('disabled', true);

                $.ajax({
                    url: $(this).data('path'),
                    data: {
                        offset: viewedConnections,
                        json: 1
                    },
                    success: function (data) {
                        $connectionContainer.append(data.html);

                        var $connectionElements = $('[data-role="connection-item"]');

                        if ((data.count == 0) || ($connectionElements.length === connectionsCount)) {
                            $('[data-role="add-more-connections"]').remove();
                        } else {
                            $this.html(normalHTML);
                            $this.detach().appendTo($connectionContainer);
                            $this.prop('disabled', false);
                        }

                        wait = false;
                    }
                });
            }
        });
    }
}