<?php
define('MAX_TIME', 24*60*60);
$folderToZip = realpath(dirname(__FILE__) . '/pdf_input');

deleteFolder($folderToZip);

function deleteFolder($folderPath) {
    $totalFile = 0;
    $current_time = time();

    if (!is_dir($folderPath)) {
        return false;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        $fileType = mime_content_type($file->getPathname());

        if ($file->isFile() && $fileType == "application/zip") {
            // only remove files before 2day
            $zip_name = $file->getFilename();
            $arr = explode(".zip", $zip_name);
            $tmp = floatval($current_time) - floatval($arr[0]);
            if( $tmp > MAX_TIME) {
                unlink($file->getPathname());
                $totalFile++;
            }
        }
    }

    if($totalFile) {
        echo $totalFile." files removed at date: ".date("d-m-Y");
    }
    else {
        echo 'No file has been deleted';
    }
}
