<?php include 'header.php'; ?>
        <h1>Your Account</h1>
        <div class="row">
            <div id="account-tabs" class="col-sm-9">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#profile" data-toggle="tab">Profile</a></li>
                    <li><a href="#audio" data-toggle="tab">Audio</a></li>
                    <li><a href="#account" data-toggle="tab">Account</a></li>
                    <li><a href="#settings" data-toggle="tab">Email Settings</a></li>
                    <li><a href="#subscription" data-toggle="tab">Subscription</a></li>
                </ul>

                <div class="tab-content">
                    
                    <!-- PROFILE TAB -->
                    <div class="tab-pane active" id="profile">
                        <form action="" method="post">
                            
                            <div class="row form-group">
                                <div class="col-sm-5">
                                    <label for="first_name">FIRST NAME:</label>
                                    <input type="text" name="first_name" value="" class="form-control">
                                </div>
                                <div class="col-sm-5">
                                    <label for="last_name">LAST NAME:</label>
                                    <input type="text" name="last_name" value="" class="form-control">
                                </div>
                            </div>
                            
                            <div class="row form-group">
                                <div class="col-sm-12">
                                    <label for="profile">PROFILE:</label>
                                    <textarea name="profile" class="form-control" rows="4"></textarea>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-5">
                                    <label for="city">CITY:</label>
                                    <input type="text" name="city" value="" class="form-control">
                                </div>
                                <div class="col-sm-5">
                                    <label for="country">COUNTRY:</label>
                                    <select type="text" name="country" value="" class="select2">
                                        <option value="">Choose a country</option>
                                    </select>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-sm-5">
                                    <label for="gender">GENDER:</label>
                                    <select type="text" name="gender" value="" class="select2">
                                        <option value="">Choose a gender</option>
                                        <option value="">Male</option>
                                        <option value="">Female</option>
                                        
                                    </select>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-sm-12 checkboxes">
                                    <label>I AM A:</label>
                                    <input type="checkbox" name="is_vocalist"> Vocalist
                                    <input type="checkbox" name="is_producer"> Producer
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-sm-5 checkboxes">
                                    <label>ACCESS TO STUDIO:</label>
                                    <input type="checkbox" name="studio_access"> YES
                                    <input type="checkbox" name="studio_access"> NO
                                </div>
                                <div class="col-sm-7 mic-field">
                                    <label for="mic"><i class="vocalizr-icon icon-mic"></i> MICROPHONE:</label>
                                    <input type="text" name="mic" value="" class="form-control">
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row form-group">
                                <div class="col-sm-5">
                                    <label for="vocal_char">VOCAL CHARACTERISTICS:</label>
                                    <select name="voccal_char" class="select2" multiple>
                                        <option>Blues</option>
                                        <option>Rough</option>
                                        <option>Strong</option>
                                        <option>Blah</option>
                                        <option>Blues 2</option>
                                        <option>Blues 3</option>
                                        <option>Blues 4</option>
                                    </select>
                                </div>
                                <div class="col-sm-5">
                                    <label for="vocal_style">VOCAL STYLE:</label>
                                    <select name="voccal_style" class="select2" multiple>
                                        <option>Blues</option>
                                        <option>Rough</option>
                                        <option>Strong</option>
                                        <option>Blah</option>
                                        <option>Blues 2</option>
                                        <option>Blues 3</option>
                                        <option>Blues 4</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-12">
                                    <label for="vocal_style">SOUNDS LIKE:</label>
                                    <input type="text" name="sounds" value="test, wtf," class="tag-input" data-placeholder="Start typing your tags">
                                </div>
                            </div>
                            
                            <hr>
                             
                            <button type="submit" name="save" value="account" class="btn btn-sm btn-default">SAVE</button>
                            
                        </form>
                        
                    </div>
                    <!-- // END PROFILE TAB -->
                    
                    <!-- AUDIO TAB -->
                    <div class="tab-pane" id="audio">
                        <form action="" method="post">
                            
                            <div class="row form-group">
                                <div class="col-sm-7">
                                    <label for="title">TITLE / ENTER TRACK NAME:</label>
                                    <input type="text" name="title" value="" class="form-control">
                                </div>
                            </div>
                            <div class="upload-audio-actions">
                                <a href="" class="btn btn-sm btn-default">UPLOAD</a>
                                <a href="" class="btn btn-sm btn-soundcloud"><i class="vocalizr-icon icon-soundcloud"></i> UPLOAD FROM SOUND CLOUD</a>
                            </div>
                            
                            <hr>
                            
                            <h2>Your profile tracks</h2>
                            <div id="account-tracks">
                                <div class="track-list-item">
                                    <div class="ui360 track-play inline"><a href="Song.mp3"><span>PLAY</span></a></div>
                                    <span class="track-title">
                                        Cause and Effect remix
                                    </span>
                                    <span class="track-length">(1:55)</span>
                                    <span class="badge badge-featured">FEATURED</span>
                                    <a href="" class="btn btn-sm btn-default remove">REMOVE</a>
                                </div>
                                
                                <div class="track-list-item">
                                    <div class="ui360 track-play inline"><a href="Song.mp3?2"><span>PLAY</span></a></div>
                                    <span class="track-title">
                                        Secret squirrel in da house yo
                                    </span>
                                    <span class="track-length">(3:37)</span>
                                    <a href="" class="btn btn-sm btn-default remove">REMOVE</a>
                                </div>
                            </div>
                        </form>
                        
                    </div>
                    <!-- // END AUDIO TAB -->
                    <div class="tab-pane" id="account">test 3</div>
                    <div class="tab-pane" id="settings">...</div>
                </div>
            </div>
            
            <div class="account-avatar col-sm-3">
                <div class="account-avatar-wrap">
                    <img src="images/avatars/eminem-lg.jpg" alt="Eminem" class="img-circle"><br>
                    <a href="" class="btn btn-sm btn-default">CHANGE IMAGE</a>
                </div>
            </div>
        </div>

<?php include 'footer.php'; ?>