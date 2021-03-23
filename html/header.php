<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="description" content="">
        <link rel="shortcut icon" href="favicon.png">

        <title>Vocalizr</title>

        <!-- Bootstrap core CSS -->
        <link href="css/bootstrap.min.css" rel="stylesheet">
        <link href="css/bootstrap-theme.css" rel="stylesheet">
        <link href="css/select2.css" rel="stylesheet">
        <link href="css/font-awesome.min.css" rel="stylesheet">
        <link href="css/tipped.css" rel="stylesheet">
        <link rel="stylesheet" type="text/css" href="css/360player.css">
        <link href="css/screen.css" rel="stylesheet">

        <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!--[if lt IE 9]>
          <script src="js/html5shiv.js"></script>
          <script src="js/respond.min.js"></script>
        <![endif]-->
    </head>

    <body>

        <!-- Wrap all page content here -->
        <div id="wrap">
            <!-- Fixed navbar -->
            <div id="header" class="navbar navbar-default navbar-static-top">
                <div class="container">
                    <div class="navbar-header">
                        <a class="navbar-brand" href="index.php"><img src="images/logo.png" alt="Vocalizr"></a>
                    </div>
                    <ul class="nav navbar-nav">
                        <li class="active"><a href="index.php">Dashboard</a></li>
                        <li><a href="gig-center.php">Gig Center</a></li>
                        <li><a href="discover.php">Discover Talent</a></li>
                        <li><a href="gig-hunter.php">Gig Hunter</a></li>
                        <li class="last"><a href="favorites.php">Favorites</a></li>
                        
                    </ul>
                </div>
            </div>
            <div id="profile-pane">
                <div class="container">
                    <div class="avatar">
                        <img src="images/avatars/eminem.jpg" alt="Eminem" class="img-circle">
                    </div>
                    <div class="profile">
                        <div class="intro">Hey, <a href="">Eminem</a></div>
                        <i id="profile-dropdown-toggle" class="vocalizr-icon icon-profile-dropdown"></i>
                        <div id="profile-dropdown" class="hide">
                            <ul id="profile-nav">
                                <li><a href="account.php"><i class="vocalizr-icon icon-settings"></i> Manage account</a></li>
                                <li><a href=""><i class="vocalizr-icon icon-finances"></i> Finances</a></li>
                                <li><a href=""><i class="vocalizr-icon icon-favorites"></i> Favorites</a></li>
                                <li><a href=""><i class="vocalizr-icon icon-logout"></i> Logout</a></li>
                            </ul>
                        </div>
                        
                        <div class="seperator"></div>
                        <div class="wallet">
                            <div class="wallet-inner">
                                You have <a id="wallet-amount" href="#wallet">$3,085</a> in you wallet
                            </div>
                        </div>
                    </div>
                    <a id="create-new-gig" href="create-gig.php" class="btn btn-primary">Create a new gig</a>
                </div>
            </div>
            
            <!-- Begin page content -->
            <div class="container">