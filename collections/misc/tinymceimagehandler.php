<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/UtilityFunctions.php');

/**
 * Based on code copied from https://www.tiny.cloud/docs/advanced/php-upload-handler/
 *
 * modified by Greg Post
 */

/***************************************************
 * Only these origins are allowed to upload images *
 ***************************************************/
$accepted_origins = array('http://localhost', UtilityFunctions::getDomain());

/*********************************************
 * Change thse lines to set the upload folder *
 *********************************************/
$imageFolder = $SERVER_ROOT . $PUBLIC_MEDIA_UPLOAD_ROOT;
$imageURL = $CLIENT_ROOT . $PUBLIC_MEDIA_UPLOAD_ROOT . '/';

if (isset($_SERVER['HTTP_ORIGIN'])) {
    // same-origin requests won't set an origin. If the origin is set, it must be valid.
    if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
        header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    } else {
        header("HTTP/1.1 403 Origin Denied");
        return;
    }
}

// Don't attempt to process the upload on an OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    header("Access-Control-Allow-Methods: POST, OPTIONS");
    return;
}

reset($_FILES);
$temp = current($_FILES);
if (is_uploaded_file($temp['tmp_name'])) {
    /*
      If your script needs to receive cookies, set images_upload_credentials : true in
      the configuration and enable the following two headers.
    */
    // header('Access-Control-Allow-Credentials: true');
    // header('P3P: CP="There is no P3P policy."');

    // Sanitize input
    if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
        header("HTTP/1.1 400 Invalid file name.");
        return;
    }

    // Verify extension
    if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "jpeg", "png"))) {
        header("HTTP/1.1 400 Invalid extension.");
        return;
    }

    // Accept upload if there was no origin, or if it is an accepted origin
    $filetowrite = $imageFolder . '/' . $temp['name'];
    if (!move_uploaded_file($temp['tmp_name'], $filetowrite)) {
        header("HTTP/1.1 500 Server Error");
        return;
    }


    // Respond to the successful upload with JSON.
    // Use a location key to specify the path to the saved image resource.
    // { location : '/your/uploaded/image/file'}
    echo json_encode(array('location' => $imageURL . $temp['name']));
} else {
    // Notify editor that the upload failed
    header("HTTP/1.1 500 Server Error");
}
