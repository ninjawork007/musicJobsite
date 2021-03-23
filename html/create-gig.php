<?php include 'header.php'; ?>
        
<div class="row">
    <div class="col-sm-8">
        <h1>Create a new gig</h1>

        <div class="light-content">
            <form action="" method="post">

                <div class="row form-group">
                    <div class="col-sm-5">
                        <label for="title">GIG TITLE:</label>
                        <input type="text" name="title" value="" class="form-control">
                    </div>
                    <div class="col-sm-5">
                        <label for="looking_for">LOOKING FOR:</label>
                        <select type="text" name="looking_for" value="" class="form-control">
                            <option value="">Who are you looking for?</option>
                        </select>
                    </div>
                </div>

                <hr>

                <div class="row form-group">
                    <div class="col-sm-8">
                        <label for="title">UPLOAD AUDIO:</label>
                        <div class="upload-audio-actions">
                            <a href="" class="btn btn-sm btn-default">UPLOAD</a>
                            <a href="" class="btn btn-sm btn-soundcloud"><i class="vocalizr-icon icon-soundcloud"></i> UPLOAD FROM SOUND CLOUD</a>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row form-group">
                    <div class="col-sm-12">
                        <div class="track-list-item">
                            <div class="ui360 track-play inline"><a href="Song.mp3"><span>PLAY</span></a></div>
                            <span class="track-title">
                                Cause and Effect remix
                            </span>
                            <span class="track-length">(1:55)</span>
                            <span class="badge badge-featured">FEATURED</span>
                            <a href="" class="btn btn-sm btn-default remove">REMOVE</a>
                        </div>
                    </div>
                </div>

                <hr>

                <div class="row form-group">
                    <div class="col-sm-5">
                        <label for="budget">BUDGET:</label>
                        <select type="text" name="budget" value="" class="form-control">
                            <option value="">$20 to $100</option>
                            <option value="">$100 to $300</option>
                        </select>
                    </div>
                    <div class="col-sm-5">
                        <label for="gender">GENDER:</label>
                        <select type="text" name="gender" value="" class="form-control">
                            <option value="">Female</option>
                            <option value="">Male</option>
                        </select>
                    </div>
                </div>

                <hr>

                <div class="row form-group">
                    <div class="col-sm-5">
                        <label for="genre">GENRE:</label>
                        <input type="text" name="genre" value="" class="form-control">
                    </div>
                </div>

                <hr>

                <div class="row form-group">
                    <div class="col-sm-12">
                        <label for="genre">DESCRIBE YOUR GIG IN DETAIL:</label>
                        <textarea name="description" class="form-control" rows="4"></textarea>
                    </div>
                </div>

                <hr>

                <div class="form-group">
                    <button name="submit" class="btn btn-sm btn-default">NEXT</button>
                </div>

            </form>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>