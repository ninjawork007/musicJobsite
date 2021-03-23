<?php

if (isset($_GET['request'])) {
    if (file_exists('ipn.txt')) {
        echo file_get_contents('ipn.txt');
    }
    if (isset($_GET['del'])) {
        @unlink('ipn.txt');
    }
    exit;
}

if (count($_POST) > 0) {
    file_put_contents('ipn.txt', http_build_query($_POST) . "\n", FILE_APPEND);
}

