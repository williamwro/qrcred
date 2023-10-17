<?php
/* Getting file name */
$filename = $_FILES['file']['name'];
$tamanho = $_FILES['file']['size'];
$mes = $_POST['mescorrente'];
$pasta = $_POST['pasta'];
/* Location */
$location = $pasta.'/'.$filename;
$uploadOk = 1;
$imageFileType = pathinfo($location,PATHINFO_EXTENSION);
/* Valid Extensions */
$valid_extensions = array("txt", "exe");
/* Check file extension */
if( !in_array(strtolower($imageFileType),$valid_extensions) ) {
    $uploadOk = 0;
}
if($uploadOk == 0){
    echo 0;
}else{
    /* Upload file */
    if(move_uploaded_file($_FILES['file']['tmp_name'],$location)){
        echo $location."/".$tamanho;
    }else{
        echo 0;
    }
}