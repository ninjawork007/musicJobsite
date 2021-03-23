<?php include 'header.php'; ?>
        <div class="row">
            <div class="col-sm-8">
                <div class="row">
                    <div class="col-sm-9">
                        <h1>That's the way i feel about 'cha</h1>
                    </div>
                    <div class="gig-header-track col-sm-3 text-right">
                        <a href="" class="gig-edit-track">EDIT TRACK</a> |
                        <span class="track-play">
                            <a href="" class="btn-play-small">PLAY</a>
                        </span>
                    </div>
                </div>
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#brief" data-toggle="tab">Brief</a></li>
                    <li><a href="#publish" data-toggle="tab">Publish</a></li>
                    <li><a href="#lyrics" data-toggle="tab">Lyrics</a></li>
                    <li><a href="#terms" data-toggle="tab">Contract & Terms</a></li>
                </ul>

                <div class="tab-content">
                    
                    <!-- PROFILE TAB -->
                    <div class="tab-pane active" id="brief">
                        <p>Enter the brief for your gig below. The more info you enter the better chance you'll have of getting someone for your gig.</p>
                        
                        <hr>
                        
                        <form action="" method="post">
                            
                            <div class="row form-group">
                                <div class="col-sm-5">
                                    <label for="budget">BUDGET:</label>
                                    <select type="text" name="budget" value="" class="form-control">
                                        <option value="">$20 to $100</option>
                                        <option value="">$100 to $300</option>
                                    </select>
                                </div>
                                <div class="col-sm-4">
                                    <label for="completed_by">COMPLETION DATE:</label>
                                    <input type="text" name="completed_by" value="" class="form-control">
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-sm-5">
                                    <label for="city">CITY:</label>
                                    <input type="text" name="city" value="" class="form-control">
                                </div>
                                <div class="col-sm-5">
                                    <label for="country">COUNTRY:</label>
                                    <select type="text" name="country" value="" class="form-control">
                                        <option value="">Choose a country</option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-sm-5">
                                    <label for="gender">GENDER:</label>
                                    <select type="text" name="gender" value="" class="form-control">
                                        <option value="">Choose a gender</option>
                                    </select>
                                </div>
                                <div class="col-sm-5">
                                    <label for="looking_for">LOOKING FOR:</label>
                                    <select type="text" name="looking_for" value="" class="form-control">
                                        <option value=""></option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-sm-12 checkboxes">
                                    <label>STUDIO ACCESS REQUIRED:</label>
                                    <input type="checkbox" name="studio_access"> YES
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row form-group">
                                <div class="col-sm-5">
                                    <label for="vocal_char">VOCAL CHARACTERISTICS:</label>
                                    <input type="text" name="first_nchar" value="" class="form-control">
                                </div>
                                <div class="col-sm-5">
                                    <label for="vocal_style">VOCAL STYLE:</label>
                                    <input type="text" name="vocal_style" value="" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-12">
                                    <label for="vocal_style">SOUNDS LIKE:</label>
                                    <input type="text" name="sounds" value="" class="form-control">
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row form-group">
                                <div class="col-sm-12">
                                    <label for="desc">GIG DESCRIPTION:</label>
                                    <textarea name="desc" class="form-control" rows="4"></textarea>
                                </div>
                            </div>
                            
                            <hr>
                             
                            <button type="submit" name="save" value="account" class="btn btn-sm btn-default">SAVE</button>
                            
                        </form>
                        
                    </div>
                    <!-- // END PROFILE TAB -->
                    
                        
                    <div class="tab-pane" id="account">test 3</div>
                    <div class="tab-pane" id="settings">...</div>
                </div>
            </div>
            
            <div class="col-sm-4">
                <div class="gig-view-panel panel panel-green">
                    <div class="panel-heading lg">
                        OPEN
                    </div>
                    <div class="panel-body">
                        <div class="panel-row time-left">
                            <span class="icon icon-time"></span> 
                            <span class="num">23</span> <small>DAYS<br> LEFT</small>
                        </div>
                        <div class="panel-row">
                            Current highest bid: <span class="highlight">$375 USD</span>
                        </div>
                        <div class="panel-row">
                            Bids made: <span class="highlight">5</span><br>
                            Average bid: <span class="highlight">$210 USD</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

<?php include 'footer.php'; ?>