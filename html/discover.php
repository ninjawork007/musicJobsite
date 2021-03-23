<?php include 'header.php'; ?>

<div class="row">
    <div class="col-sm-8 discover-items">
        <h1>Discover talent</h1>
        <div class="discover-item">
            <div class="media">
                <div class="avatar">
                    <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle">
                </div>                        
                <div class="ui360 track-play block-player"><a href="Song.mp3"><span>PLAY</span></a></div>
            </div>

            <div class="info">
                <div class="name"><a href="">Mariah Carey</a></div>
                <div class="location">Los Angeles, CA</div>
                <div class="sounds-like">
                    <ul>
                        <li class="title">Sounds like:</li>
                        <li>Richard Branson</li>
                        <li>Cliff Richard</li>
                        <li>Johhny Young</li>
                        <li>Robert Maxdog</li>
                        <li>Peter Russell Clarke</li>
                        <li>George Bush Snr</li>
                    </ul>
                </div>
                <div class="vocal-style">
                    <ul>
                        <li class="title">Vocal Style:</li>
                        <li>Spoken word</li>
                        <li>Rock</li>
                    </ul>
                </div>
                <div class="studio-access">
                    <ul>
                        <li class="title">Studio Access:</li>
                        <li>YES</li>
                    </ul>
                </div>
                <div class="microphone">
                    <ul>
                        <li class="title">Microphone:</li>
                        <li>Shure SM58</li>
                    </ul>
                </div>
            </div>

            <div class="footer">
                <a href="" class="btn btn-sm btn-default invite roll-alt" data-id="1"><i class="icon icon-chevron-down"></i> INVITE TO GIG</a>
                <a href="" class="btn btn-sm btn-default add-to-favourite roll-alt"><i class="icon icon-star"></i> ADD TO FAVOURITES</a>
                <a href="" class="reviews">12 Reviews</a>
                <div class="star-rating star-disabled" data-score="4"></div>
                <div class="rating-text">5 ratings</div>
            </div>
            <div id="gigs-dropdown-1" class="hide">
                <ul class="gigs-tip-list">
                    <li><a href="">Spin that shit till it melts</a></li>
                    <li><a href="">Can't light the spark</a></li>
                    <li><a href="">Get your shizzle on</a></li>
                    <li><a href="">All that you want is gone</a></li>
                    <li><a href="">Go</a></li>
                </ul>
            </div>
        </div>
        <div class="discover-item">
            <div class="media">
                <div class="avatar">
                    <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle">
                </div>                        
                <div class="ui360 track-play block-player"><a href="Song.mp3?2"><span>PLAY</span></a></div>
            </div>

            <div class="info">
                <div class="name"><a href="">Mariah Carey</a></div>
                <div class="location">Los Angeles, CA</div>
                <div class="sounds-like">
                    <ul>
                        <li class="title">Sounds like:</li>
                        <li>Richard Branson</li>
                        <li>Cliff Richard</li>
                        <li>Johhny Young</li>
                        <li>Robert Maxdog</li>
                        <li>Peter Russell Clarke</li>
                        <li>George Bush Snr</li>
                    </ul>
                </div>
                <div class="vocal-style">
                    <ul>
                        <li class="title">Vocal Style:</li>
                        <li>Spoken word</li>
                        <li>Rock</li>
                    </ul>
                </div>
                <div class="studio-access">
                    <ul>
                        <li class="title">Studio Access:</li>
                        <li>YES</li>
                    </ul>
                </div>
                <div class="microphone">
                    <ul>
                        <li class="title">Microphone:</li>
                        <li>Shure SM58</li>
                    </ul>
                </div>
            </div>

            <div class="footer">
                <a href="" class="btn btn-sm btn-default invite roll-alt" data-id="2"><i class="icon icon-chevron-down"></i> INVITE TO GIG</a>
                <a href="" class="btn btn-sm btn-default add-to-favourite roll-alt"><i class="icon icon-star"></i> ADD TO FAVOURITES</a>
                <a href="" class="reviews">12 Reviews</a>
                <div class="star-rating star-disabled" data-score="4"></div>
                <div class="rating-text">5 ratings</div>
            </div>
            <div id="gigs-dropdown-2" class="hide">
                <ul class="gigs-tip-list">
                    <li><a href="">Spin that shit till it melts</a></li>
                    <li><a href="">Can't light the spark</a></li>
                    <li><a href="">Go</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="discover-filter-panel panel panel-default">
            <div class="panel-heading">
                REFINE YOUR SEARCH
            </div>
            <div class="panel-body padding">

                <div class="row form-group">
                    <div class="col-sm-12">
                        <label>SOUNDS LIKE:</label>
                        <inputname="sounds_like" class="form-control">
                    </div>
                </div>
                
                <hr>
                
                <div class="row form-group">
                    <div class="col-sm-12">
                        <select type="text" name="gender" value="" class="select2">
                            <option value="">Choose a gender</option>
                        </select>
                    </div>
                </div>
                
                <hr>
                
                <div class="row form-group">
                    <div class="col-sm-12">
                        <input type="checkbox" name="studio_access" value="1"> Must have studio access
                    </div>
                </div>
                
                <hr>
                
                <div class="row form-group">
                    <div class="col-sm-12">
                        <label>GENRES:</label>
                        <select name="genres" value="" class="select2" multiple>
                            <option value="">Select Genres</option>
                            <option value="">Rap</option>
                            <option value="">RnB</option>
                        </select>
                    </div>
                </div>
                
                <hr>
                
                <div class="row form-group">
                    <div class="col-sm-12">
                        <select name="language" class="select2">
                            <option value="">Choose a vocal style</option>
                        </select>
                    </div>
                </div>

                <div class="row form-group">
                    <div class="col-sm-12">
                        <select name="language" value="" class="select2">
                            <option value="">Choose a vocal characteristic</option>
                        </select>
                    </div>
                </div>
                
                <hr>

                <div class="row form-group">
                    <div class="col-sm-12">
                        <label>LOCATION:</label>
                        <input type="text" name="location" class="form-control">
                    </div>
                </div>
                
                <hr>
                
                <div class="form-buttons">
                    <button type="submit" name="search" class="btn btn-sm btn-default">SEARCH</button>
                    <button type="button" name="reset" class="btn btn-sm btn-default roll-alt">RESET</button>
                </div>
            </div>
        </div>  
    </div>
</div>

<?php include 'footer.php'; ?>