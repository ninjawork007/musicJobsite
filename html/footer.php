            </div>
        </div>

        <div id="footer">
            <div class="container">
                <div class="copyright">
                    &copy; Copyright Vocalizr 2014
                </div>
            </div>
        </div>
        
        
        <!-- Modal -->
        <div class="modal fade" id="bidModal" tabindex="-1" role="dialog" aria-labelledby="bidLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                        <h4 class="modal-title">Place your bid</h4>
                    </div>
                    <div class="modal-sub-header">
                        TITLE: <span>That's the way i feel about 'cha</span>
                    </div>
                    <div class="modal-body">
                        <form action="" method="post">
                            <div class="row modal-row">
                                <div class="bid-amount col-sm-4">
                                    <label>BID AMOUNT (USD)</label>
                                    <input type="text" name="" class="form-control">
                                </div>
                                <div class="bid-gig-details col-sm-6">
                                    <div class="budget">Budget: <span class="white-highlight">$500-900 USD</span></div>
                                    <div class="highest-bid">Current highest bid: <span class="white-highlight">$375 USD</span></div>
                                    <div class="bids-made">Bids made: <span class="white-highlight">5</span></div>
                                </div>
                            </div>
                            <div class="modal-row">
                                <label>UPLOAD AUDIO: </label><span class="help-note">MP3 FILES ONLY</span>
                                <div id="bid-audio-options">
                                    <a href="" class="btn-record">RECORD AUDITION</a>
                                    <span class="or">OR</span>
                                    <a href="" class="btn btn-sm btn-default">CHOOSE FILE</a>
                                </div>
                                <div class="track-list-item">
                                    <div class="ui360 track-play"><a href="Song.mp3"><span>PLAY</span></a></div>
                                    <span class="track-title">
                                        Secret squirrel in da house yo
                                    </span>
                                    <span class="track-length">(3:37)</span>
                                    <a href="" class="btn btn-sm btn-default remove">REMOVE</a>
                                </div>
                            </div>
                            <div class="modal-row">
                                <a id="place-bid-now" class="btn btn-primary" data-toggle="modal" href="#bidModal"><i class="vocalizr-icon icon-white-thumb"></i> Place your bid now</a>
                            </div>
                        </form>
                    </div>
                </div><!-- /.modal-content -->
            </div><!-- /.modal-dialog -->
        </div><!-- /.modal -->

        <script type="text/javascript" src="js/jquery.js"></script>
        <script type="text/javascript" src="js/bootstrap.min.js"></script>
        <script type="text/javascript" src="js/tipped.js"></script>
        <script type="text/javascript" src="js/jquery.raty.min.js"></script>
        <script type="text/javascript" src="js/select2.min.js"></script>
        <script type="text/javascript" src="js/jquery.icheck.min.js"></script>
        <!-- special IE-only canvas fix -->
        <!--[if IE]><script type="text/javascript" src="js/excanvas.js"></script><![endif]-->

        <!-- Apache-licensed animation library -->
        <script type="text/javascript" src="js/berniecode-animator.js"></script>

        <!-- the core stuff -->
        <script type="text/javascript" src="js/soundmanager2.js"></script>
        <script type="text/javascript" src="js/page-player.js"></script>
        <script type="text/javascript" src="js/360player.js"></script>
        <script type="text/javascript" src="js/app.js"></script>
        
        <script>
            jQuery.extend(Tipped.Skins, {
              'profile-nav' : {
                border: { size: 4, color: '#14b9d6' },
                background: '#14b9d6',
                radius: { size: 4, position: 'border' },
                shadow: true
              }
            });
            jQuery.extend(Tipped.Skins, {
              'gigs-select' : {
                background: { color: '#18242b', opacity: .95 },
                radius: { size: 4, position: 'border' },
                shadow: false,
                fadeIn: 0,
                fadeOut: 0,
                radius: 4,
              }
            });
            
            
            $(function ()
            {
                Tipped.create("#profile-dropdown-toggle", $('#profile-dropdown').html(), {
                  skin: 'profile-nav',
                  hook: 'bottommiddle'
                }); 

                $('.invite').each(function() {
                    var eId = $(this).data('id');
                    Tipped.create(this, $('#gigs-dropdown-' + eId).html(), {
                        skin: 'gigs-select', 
                        hook: 'bottommiddle'
                    });
                });
                
                $('.star-disabled').raty({ 
                    path: 'images',
                    readOnly: true,
                    width: 93,
                    score: function() {
                      return $(this).attr('data-score');
                    }
                });
                
                $('.star-enabled').raty({ 
                    path: 'images',
                    score: function() {
                      return $(this).attr('data-score');
                    }
                });
                
                /* Custom select / tags */
                $('.select2').select2();
                
                $('.tag-input').each(function () {
                    $(this).select2({
                        tags: [],
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
                            return 'Type what you sound like and press enter'
                        }
                    });
                });
                
                /* Custom checkboxes */
                $('input').iCheck({
                    checkboxClass: 'icheckbox_polaris',
                    radioClass: 'iradio_polaris'
                });

                $('.add-gig-comment, .cancel-gig-comment').on('click', function(e) {
                    e.preventDefault();
                    $('.new-comment').slideToggle();
                });
                
                soundManager.setup({
                  // path to directory containing SM2 SWF
                  url: 'swf/'
                });


            });
        </script>
    </body>
</html>
