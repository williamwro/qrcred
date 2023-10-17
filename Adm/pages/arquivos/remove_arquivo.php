<?php
$path = $_POST['path'];
$return_text = 0;

// Check file exist or not
if( file_exists($path) ){
    // Remove file
    unlink($path);

    // Set status
    $return_text = 1;
}else{
    // Set status
    $return_text = 0;
}

// Return status
echo $return_text;
exit;
