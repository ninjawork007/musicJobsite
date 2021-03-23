<?php include 'header.php'; ?>

<div class="row">
    <div class="col-sm-12 gig-header">
        <h1>That's the way I feel about cha</h1>
        <div class="avatar">
            <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle img-mini">
        </div>          
        <a href>NoviBoy</a> created this gig <span class="time-ago">1 day ago</span>
        <ul class="genres">
            <li>Genre:</li>
            <li>Progressive House</li>
            <li>Techno</li>
        </ul>
        <a href class="btn btn-sm btn-default pull-right">PROMPT FOR ASSETS</a>
        <hr>
        <div class="gig-track">
            <div class="track-play">
                <a href="" class="btn-play">PLAY</a>
            </div>
            <div class="track-wrap">
                <div class="track-label">MASTER TRACK</div>
                <a class="edit-track">EDIT TRACK</a>
                <div class="waveform full"></div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-sm-12 gig-studio">

        <ul class="nav nav-tabs">
            <li><a href="studio.php">Studio</a></li>
            <li class="active"><a href="lyrics.php">Lyrics</a></li>
            <li><a href="#assets" data-toggle="tab">Assets</a></li>
            <li><a href="#payment" data-toggle="tab">Payment</a></li>
        </ul>
        
        <div class="tab-content tab-gig-lyrics">
            <div class="row">
                <div class="col-sm-8">
                    <form class="form">
                        <div class="form-group">
                            <textarea class="col-sm-12 current-lyrics">
Yo
Ho
Here is the loro
Ics
                            </textarea>
                            </div>
                            <div class="form-buttons">
                                <button type="submit" class="btn btn-sm btn-default">SAVE LYRICS</button>
                            </div>
                    </form>
                </div>
                <div class="col-sm-4">
                    <div class="lyrics-versions">
                        <div class="heading">LYRICS UPDATE HISTORY:</div>
                        <div class="version current">
                            <div class="arrow"><div class="inner"></div></div>
                            <div class="avatar">
                                <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle img-mini">
                            </div>        
                            <div class="info">
                                <div class="version-number">Current</div>
                                <div class="time-ago">12 minutes ago</div>
                                <div><a href>NoviBoy</a></div>
                            </div>

                        </div>
                        <div class="version">
                            <div class="avatar">
                                <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle img-mini">
                            </div>        
                            <div class="info">
                                <div class="version-number">Version 2.</div>
                                <div class="time-ago">12 minutes ago</div>
                                <div><a href="profile.php">NoviBoy</a></div>
                            </div>

                        </div>
                        <div class="version">
                            <div class="avatar">
                                <img src="images/avatars/sandy.jpg" alt="sandy" class="img-circle img-mini">
                            </div>        
                            <div class="info">
                                <div class="version-number">Version 1.</div>
                                <div class="time-ago">12 minutes ago</div>
                                <div><a href="profile.php">NoviBoy</a></div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>