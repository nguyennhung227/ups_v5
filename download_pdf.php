<?php
require_once('main_function.php');
if(isset($_GET['name'])) {
    $folder_name = trim($_GET['name']);
    $folderInputPath = realpath(dirname(__FILE__) . '/pdf_input');
    $folderPath = realpath(dirname(__FILE__) . '/pdf_input').'/'.$folder_name;
    $folderConvertPath = realpath(dirname(__FILE__) . '/pdf_input').'/'.$folder_name.'/convert_pdf';
    $zip_path = $folderPath.".zip";
    $zip_link = DOMAIN.'pdf_input/'.$folder_name.'.zip';

    if(checkIsZip($zip_path)) {
        // export to zip
        echo 'Zip link: <a href="'. $zip_link .'">'.$zip_link.'</a>';
        return false;
    }

    if (!is_dir($folderPath)) {
        echo "No files";
        return false;
    }

    if (!is_dir($folderConvertPath)) {
        mkdir($folderConvertPath, 0777, true);
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    $filesConvert = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folderConvertPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    // 1. Convert PDF
    $flag = convertPdf($folder_name);

    if($flag) {
        // 2. Export to zip
        // Đường dẫn thư mục chứa các tệp tin PDF
        $zipFilename = $folderInputPath.'/'.$folder_name.".zip";

        // Tạo tệp nén
        $zip = new ZipArchive();
        // Open the zip archive for writing

        if ($zip->open($zipFilename, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
            // Add all files and subdirectories from the source folder to the zip
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($folderConvertPath),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $name => $file) {
                if (!$file->isDir()) {
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($folderConvertPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }

            // Close the zip archive
            $zip->close();

            // Xóa convert folder and upload folder
            deleteFolder($folderPath);

            echo 'Zip archive created successfully.<br/>';
            // export to zip
            echo 'Zip link: <a href="'. $zip_link .'">'.$zip_link.'</a>';
        } else {
            echo 'Failed to create zip archive.';
        }
    }
}