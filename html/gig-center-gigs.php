<?php include 'header.php'; ?>

<div class="row">
    <div class="col-sm-12">
        <h1>Gig Center - Gigs</h1>
    
        <div class="panel panel-default">
            <div class="panel-heading">
                <ul class="gc-nav">
                    <li class="gc-nav-item"><a href>Activity</a></li>
                    <li class="gc-nav-item active"><a href>Gigs (23)</a></li>
                    <li class="gc-nav-item"><a href>Your bids (2)</a></li>
                    <li class="gc-nav-item"><a href>Invites (0)</a></li>
                </ul>
            </div>
            <div class="panel-body gc-gigs">
                <ul class="gc-sub-nav">
                    <li class="gc-sub-nav-item active"><a href>In Production (3)</a></li>
                    <li class="gc-sub-nav-item"><a href>Pre Production (1)</a></li>
                    <li class="gc-sub-nav-item"><a href>Completed (0)</a></li>
                    <li class="gc-sub-nav-item"><a href>Expired (0)</a></li>
                </ul>
        
                <div list="active-gigs">
                    <div class="active-gigs-item">
                        <div class="gig-owner pull-right">
                            <a href="profile.php">Eminem</a> <img src="images/avatars/eminem.jpg" class="img-circle img-mini">
                        </div>
                        <a href="gig.php" class="gig-title">Gig title is here like this</a>
                        <div class="due-date"><span>Due Date:</span> 23rd November, 2013</div>
                        <div class="comment-content">
                            <a href="profile.php">You</a> said... <span class="time-ago">5 minutes ago</span>
                            <p class="comment">
                                "Let me know what you think of the latest audio."
                            </p>
                        </div>
                    </div>
                    <div class="active-gigs-item alt">
                        <div class="gig-owner pull-right">
                            <a href="profile.php">Eminem</a> <img src="images/avatars/eminem.jpg" class="img-circle img-mini">
                        </div>
                        <a href="gig.php" class="gig-title">Gig title is here like this</a>
                        <div class="due-date"><span>Due Date:</span> 23rd November, 2013</div>
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
                    <div class="active-gigs-item">
                        <div class="gig-owner pull-right">
                            <a href="profile.php">Eminem</a> <img src="images/avatars/eminem.jpg" class="img-circle img-mini">
                        </div>
                        <a href="gig.php" class="gig-title">Gig title is here like this</a>
                        <div class="due-date"><span>Due Date:</span> 23rd November, 2013</div>
                        <div class="comment-content">
                            <a href="profile.php">You</a> said... <span class="time-ago">5 minutes ago</span>
                            <p class="comment">
                                "Let me know what you think of the latest audio."
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>