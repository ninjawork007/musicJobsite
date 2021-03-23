<?php include 'header.php'; ?>


<div class="row">
    <div class="col-sm-12 gig-header">
        <h1>That's the way I feel about cha</h1>
        <div class="avatar">
            <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle img-mini">
        </div>          
        <a href="profile.php">NoviBoy</a> created this gig <span class="time-ago">1 day ago</span>
        <ul class="genres">
            <li>Genre:</li>
            <li>Progressive House</li>
            <li>Techno</li>
        </ul>
        <a href class="btn btn-sm btn-default pull-right">PROMPT FOR ASSETS</a>
        <hr>
        <div class="gig-track">
            <div class="playlist track-waveform">
                <div class="track-label">
                    <span>MASTER TRACK</span> <a href="" class="link">EDIT TRACK</a>
                </div>
                <a href="Song.mp3" class="track">PLAY</a>
                <img src="images/songmp3-305f104d.png">
                <img src="images/songmp3-305f104d-roll.png" class="roll hide">
            </div>
            <!--
            <div class="track-wrap">
                <div class="track-label">MASTER TRACK</div>
                <a class="edit-track">EDIT TRACK</a>
                <div class="waveform"><img src="images/wave.png"></div>
            </div>
            -->
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12 gig-studio">

        <ul class="nav nav-tabs">
            <li class="active"><a href="studio.php">Studio</a></li>
            <li><a href="lyrics.php">Lyrics</a></li>
            <li><a href="#account" data-toggle="tab">Assets</a></li>
            <li><a href="#settings" data-toggle="tab">Payment</a></li>
        </ul>
        
        <div class="tab-content tab-gig-studio">
            <div class="latest-audio">
                <div class="gig-track">
                    <div class="playlist track-waveform">
                        <div class="track-label">
                            <span>LATEST RECORDING</span> <a href="" class="link">UPDATE RECORDING</a>
                        </div>
                        <a href="Song.mp3" class="track">PLAY</a>
                        <img src="images/songmp3-305f104d.png">
                        <img src="images/songmp3-305f104d-roll.png" class="roll hide">
                    </div>
                </div>
            </div>
            <div class="new-comment-wrap">
                <a href class="btn btn-sm btn-default add-gig-comment">ADD COMMENT</a>
                <div class="new-comment row">
                    <form class="form-inline">
                        <div class="form-group col-sm-12">
                            <label class="sr-only"  for="vocal_style">Enter your comment</label>
                            <textarea name="sounds" value="" class="form-control" placeholder="Enter your comment..."></textarea>
                        </div>
                        <div class="form-buttons col-sm-12">
                            <button type="submit" class="btn btn-sm btn-default">ATTACH AUDIO</button>
                            <button type="submit" class="btn btn-sm btn-default pull-right">SUBMIT</button>
                            <button type="submit" class="btn btn-sm btn-default pull-right roll-alt cancel-gig-comment">CANCEL</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="comment-history">
                <div class="comment-wrap">
                    <div class="avatar">
                        <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle img-mini">
                    </div>     
                    <div class="comment-content">
                        <a href="profile.php">Rob</a> said... <span class="time-ago">5 minutes ago</span>
                        <p class="comment">
                            "Time for a break bitches!"
                        </p>
                    </div>     
                </div>
                <div class="comment-wrap">
                    <div class="avatar">
                        <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle img-mini">
                    </div>     
                    <div class="comment-content">
                        <a href="profile.php">NoviBoy</a> said... <span class="time-ago">5 minutes ago</span>
                        <p class="comment">
                            "Hey punk, this is the message that i am sending you, you need to get the fuck on your phone coz that's me i've been calling you for days now,
                            i’m busting a blood vessel. Got some great work for you but something we need you in the studio by Tuesday arvo."
                        </p>
                    </div>     
                </div>
                <div class="comment-wrap">
                    <div class="avatar">
                        <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle img-mini">
                    </div>     
                    <div class="comment-content">
                        <a href="profile.php">NoviBoy</a> said... <span class="time-ago">5 minutes ago</span>
                        <p class="comment">
                            "Hey punk, this is the message that i am sending you, you need to get the fuck on your phone coz that's me i've been calling you for days now,
                            i’m busting a blood vessel. Got some great work for you but something we need you in the studio by Tuesday arvo."
                        </p>
                    </div>     
                </div>
                <div class="comment-wrap">
                    <div class="avatar">
                        <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle img-mini">
                    </div>     
                    <div class="comment-content">
                        <a href="profile.php">NoviBoy</a> said... <span class="time-ago">5 minutes ago</span>
                        <p class="comment">
                            "Hey punk, this is the message that i am sending you, you need to get the fuck on your phone coz that's me i've been calling you for days now,
                            i’m busting a blood vessel. Got some great work for you but something we need you in the studio by Tuesday arvo."
                        </p>
                        <div class="audio"> 
                        <div class="gig-track">
                            <div class="playlist track-waveform small">
                                <a href="Song.mp3" class="track">Play</a>
                                <img src="images/songmp3-305f104d.png">
                                <img src="images/songmp3-305f104d-roll.png" class="roll hide">
                            </div>
                        </div>
                        </div>
                    </div>     
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>