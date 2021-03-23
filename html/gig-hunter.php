<?php include 'header.php'; ?>

<div class="row">
    <div class="col-sm-8">
        <h1>Gig Hunter</h1>
        
        <div id="gig-results">
            <div class="gig-list-item">
                <div class="gig-owner">
                    <a href="profile.php">Eminem</a> <img src="images/avatars/eminem.jpg" class="img-circle">
                </div>
                <a href="gig.php" class="gig-title">Gig title is here like this</a><br>
                <div class="gig-created">2 weeks ago</div>
            </div>
            
            <div class="gig-list-item">
                <div class="gig-owner">
                    <a href="profile.php">Eminem</a> <a href="profile.php"><img src="images/avatars/eminem.jpg" class="img-circle"></a>
                </div>
                <a href="gig.php" class="gig-title">Gig title is here like this</a><br>
                <div class="gig-created">2 weeks ago</div>
            </div>
            
            <div class="gig-list-item">
                <div class="gig-owner">
                    <a href="profile.php">Eminem</a> <a href="profile.php"><img src="images/avatars/eminem.jpg" class="img-circle"></a>
                </div>
                <a href="gig.php" class="gig-title">Gig title is here like this</a><br>
                <div class="gig-created">2 weeks ago</div>
            </div>
            
            <div class="gig-list-item">
                <div class="gig-owner">
                    <a href="profile.php">Eminem</a> <a href="profile.php"><img src="images/avatars/eminem.jpg" class="img-circle"></a>
                </div>
                <a href="gig.php" class="gig-title">Gig title is here like this</a><br>
                <div class="gig-created">2 weeks ago</div>
            </div>
            
            <div class="gig-list-item">
                <div class="gig-owner">
                    <a href="profile.php">Eminem</a> <a href="profile.php"><img src="images/avatars/eminem.jpg" class="img-circle"></a>
                </div>
                <a href="gig.php" class="gig-title">Gig title is here like this</a><br>
                <div class="gig-created">2 weeks ago</div>
            </div>
            
            <div class="gig-list-item">
                <div class="gig-owner">
                    <a href="profile.php">Eminem</a> <a href="profile.php"><img src="images/avatars/eminem.jpg" class="img-circle"></a>
                </div>
                <a href="gig.php" class="gig-title">Gig title is here like this</a><br>
                <div class="gig-created">2 weeks ago</div>
            </div>
            
            <div class="paging">
                <ul>
                    <li><a href="#" class="direction">&larr; Prev</a></li>
                    <li><a href="#">1</a></li>
                    <li class="active"><a href="#">2</a></li>
                    <li><a href="#">3</a></li>
                    <li><a href="#">4</a></li>
                    <li><a href="#">5</a></li>
                    <li><a href="#" class="direction">Next &rarr;</a></li>
                </ul>
            </div>
        </div>
        
    </div>
    <div class="col-sm-4">
        <div class="search-panel panel panel-default">
            <div class="panel-heading">
                REFINE YOUR SEARCH
            </div>
            <div class="panel-body padding">
                
                <div class="row form-group">
                    <div class="col-sm-12">
                        <label>SOUNDS LIKE:</label>
                        <input type="text" name="sounds_like" class="form-control">
                    </div>
                </div>
                
                <hr>
                
                <div class="row form-group">
                    <div class="col-sm-12">
                        <select name="gender" class="select2">
                            <option value="">Choose a gender</option>
                            <option value="">Male</option>
                            <option value="">Female</option>
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
                        <select name="language" value="" class="select2">
                            <option value="">Choose a vocal characteristic</option>
                        </select>
                    </div>
                </div>
                
                <hr>
                
                <div class="form-buttons">
                    <button type="submit" name="search" class="btn btn-sm btn-default">SEARCH</button>
                    <button type="button" name="reset" class="btn btn-sm btn-default reset">RESET</button>
                </div>

            </div>
        </div>
    </div>
    
</div>

<?php include 'footer.php'; ?>