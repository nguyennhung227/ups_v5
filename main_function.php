<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
define('LIMIT_API', 50);
define('DOMAIN', 'https://kuteweb.com/ups_v5/');

require_once('vendor/autoload.php');
use setasign\Fpdi\Tcpdf\Fpdi;

function convertPdf($folder_name) {
    $folderPath = realpath(dirname(__FILE__) . '/pdf_input').'/'.$folder_name;

    $files = new DirectoryIterator($folderPath);
    $count = 0;

    $zip_path = $folderPath.".zip";
    if(!checkIsZip($zip_path)) {
        foreach ($files as $item) {
            if($count == LIMIT_API) {
                break;
            }

            $fakeImage = getFakeImage($folderPath);
            $pdf_file_name = $item->getFilename();
            // $fileType = mime_content_type($item->getPathname());
            $fileType = pathinfo($item->getPathname(), PATHINFO_EXTENSION);
            if ($item->isFile() && $fileType == "pdf") {
                // 1. Resize PDF
                $rotateFile = $folderPath."/convert_pdf/rotate_".$pdf_file_name;
                $outputFile = $folderPath."/convert_pdf/".$pdf_file_name;
                resizePdf($rotateFile, $outputFile);

                // 2. Update fake address if have
                var_dump($fakeImage);
                if( $fakeImage != "" ) {
                    replacePdf($outputFile, $outputFile, $fakeImage);
                }

                unlink($rotateFile);

                $count++;
            }
        }

        echo "<br/>".$count." files have been convert<br/>";
        if($count > 0) {
            return true;
        }
        else {
            return false;
        }
    }

    return true;
}

function getFakeImage($folderPath)
{
    $imageFake = "";
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $item) {
        // $fileType = mime_content_type($item->getPathname());
        $fileType = pathinfo($item->getPathname(), PATHINFO_EXTENSION);

        if ($item->isFile() && $fileType == "png") {
            $imageFake = $item->getPathname();

            // check window or ios
            $fileSize = filesize($imageFake);
            if($fileSize) {
                break;
            }
        }
    }
    return $imageFake;
}

function rotatePdf($sourceFile, $outputFile) {
    // Create a new instance of FPDI using TCPDF
    $pdf = new Fpdi();

// Add a page from the source PDF
    $pageCount = $pdf->setSourceFile($sourceFile);
    $templateId = $pdf->importPage(1);

// Get the dimensions of the imported page
    $pageWidth = $pdf->getTemplateSize($templateId)['width'];
    $pageHeight = $pdf->getTemplateSize($templateId)['height'];

    $newWidth = $pageHeight/2 ;
    $newHeight = $pageWidth/2 + 0.5 ;
    $arr_new = [$newWidth, $newHeight];

// Add a new page with rotated content
    $pdf->AddPage("P", $arr_new);

    $pdf->Rotate(-90);
    $pdf->useTemplate($templateId, -11.4, -$newWidth + 10.7, $pageWidth, $pageHeight);
    $pdf->Output($outputFile, 'F'); // F: save to file
}

function cropPdfWindow($sourceFile, $outputFile) {
    $pdf = new Fpdi();
// Import a page from the source PDF
    $pageCount = $pdf->setSourceFile($sourceFile);
    $templateId = $pdf->importPage(1); // Change 1 to the desired page number

// Get the dimensions of the imported page
    $size = $pdf->getTemplateSize($templateId);
    $pageWidth = $size['width'];
    $pageHeight = $size['height'];

    $newWidth = 106;
    $newHeight = 150;

// Add a page to the PDF
    $pdf->AddPage('P', array($newWidth, $newHeight));
// Calculate the dimensions for cropping
    $cropSize = array(
        'width' => $pageWidth,
        'height' => $pageHeight,
        'x' => -14.2,
        'y' => -30.2
    );
    $pdf->Rotate(-90);
    $pdf->useTemplate($templateId, $cropSize['x'], -115.5, $cropSize['width'], $cropSize['height']);

// Output the cropped PDF
    $pdf->Output($outputFile, 'F');
}

function resizePdf($sourceFile, $outputFile) {
    $pdf = new Fpdi();
    $pageCount = $pdf->setSourceFile($sourceFile);
    $templateId = $pdf->importPage(1);

    $newWidth = 237;
    $newHeight = 336;
    $arr_new = [$newWidth, $newHeight];

    $pdf->AddPage('P', $arr_new);
    $pdf->Rotate(0); // Reset rotation if needed

// Use the imported template on the new page, scaled to the zoomed dimensions
    $pdf->useTemplate($templateId, 0, 0, $newWidth, $newHeight);

// Output the zoomed PDF to the browser
    $pdf->Output($outputFile, 'F');
}

function replacePdf($sourceFile, $outputFile, $fakeImage) {
    $pdf = new Fpdi();

// Add a page from the input PDF
    $pageCount = $pdf->setSourceFile($sourceFile);
    $templateId = $pdf->importPage(1); // Import the first page

// Get the size of the imported page
    $size = $pdf->getTemplateSize($templateId);

// Add a page to the PDF
    $pdf->AddPage($size['orientation'], array($size['width'], $size['height']));

// Replace text (assuming you have a specific text you want to replace)
//    $modifiedText = "EMMANUEL INC\n7149817979\n12382 GARDEN GROVE BLVD #210 GARDEN GROVE CA 92843";

// Use the imported page as a template
    $pdf->useTemplate($templateId);

// Now, you can draw on the PDF to cover the text you want to remove
    $pdf->SetFillColor(255, 255, 255); // Set the fill color to white
    $pdf->Rect(5, 2.7, 100, 32, 'F'); // Replace x1, y1, width, and height with appropriate values

// Set the modified text content
//$pdf->SetFont('Helvetica','B', 16);
//$pdf->SetXY(20, 50); // Set position
//$pdf->MultiCell(100, -1, $modifiedText, 0, 'L'); // Replace existing text with new content

// Replace text area with an image
    $imageX = 5; // X-coordinate of the image
    $imageY = 3; // Y-coordinate of the image
    $imageWidth = 100; // Width of the image
    $imageHeight = 32; // Height of the image
    $pdf->Image($fakeImage, $imageX, $imageY, $imageWidth, $imageHeight);
// Output the modified PDF to the output file
    $pdf->Output($outputFile, 'F');
}

function checkIsZip($filename) {
    if (file_exists($filename)) {
        return true;
    } else {
        return false;
    }
}

function deleteFolder($folderPath) {
    if (!is_dir($folderPath)) {
        return false;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folderPath, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    return rmdir($folderPath);
}
