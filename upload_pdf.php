<?php
error_reporting(E_ALL);

require_once('main_function.php');

if( isset($_POST['submit'])
    && isset($_FILES['fileToUpload'])
    && $_FILES['fileToUpload']['size'][0] > 0
){
    $pdf_files = $_FILES["fileToUpload"];
   

    $countRotate = 0;
// Upload PDF
    $folder_name = time();
    $targetDirectory = realpath(dirname(__FILE__) . '/pdf_input')."/".$folder_name."/";
    if (!is_dir($targetDirectory)) {
        mkdir($targetDirectory, 0777, true);
    }

    $message_success = "";
    $message_error = "";

// Loop through each uploaded file
    foreach ($pdf_files["tmp_name"] as $key => $tmp_name) {
        $uploadOk = 1;
        $targetFile = $targetDirectory . basename($pdf_files["name"][$key]);
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Check if the file is a PDF
        if ($fileType != "pdf") {
            $message_error .= basename($pdf_files["name"][$key])." isn't PDF file.<br/>";
            $uploadOk = 0;
            continue;
        }

        // Check if file already exists
        if (file_exists($targetFile)) {
            $message_error .= basename($pdf_files["name"][$key])." has already exists.<br/>";
            $uploadOk = 0;
            continue;
        }

        // Check file size (you can adjust the size as needed)
        if ($pdf_files["size"][$key] > 500000) {
            $message_error .= basename($pdf_files["name"][$key])." is too large size.<br/>";
            $uploadOk = 0;
            continue;
        }

        if ($uploadOk == 0) {
            $message_error .= basename($pdf_files["name"][$key])."<br/>";
        } else {
            if (move_uploaded_file($tmp_name, $targetFile)) {
                $message_success .= basename($pdf_files["name"][$key]). "<br>";
            } else {
                $message_error .= basename($pdf_files["name"][$key])."<br/>";
            }
        }
    }

// Upload fake image if have
    if( isset($_FILES["fakeAddress"])
        && $_FILES['fakeAddress']['size'] > 0
    ){
        $fake_address_img = $_FILES["fakeAddress"];
        $targetFile = $targetDirectory . basename($fake_address_img["name"]);
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        // Check if the file is a PDF
        if ($fileType != "png") {
            $message_error .= basename($fake_address_img["name"])." isn't PDF file.<br/>";
        }
        // Check file size (you can adjust the size as needed)
        else if ($fake_address_img["size"] > 500000) {
            $message_error .= basename($fake_address_img["name"])." is too large size.<br/>";
        }
        else {
            $targetImageFile = $targetDirectory . basename($fake_address_img["name"]);

            if (move_uploaded_file($fake_address_img['tmp_name'], $targetFile)) {
//                $message_success .= basename($fake_address_img["name"]). "<br>";
            } else {
                $message_error .= basename($fake_address_img["name"])."<br/>";
            }
        }
    }

// Rotate PDF
    $folderConvertPath = realpath(dirname(__FILE__) . '/pdf_input').'/'.$folder_name.'/convert_pdf';

    if (!is_dir($folderConvertPath)) {
        mkdir($folderConvertPath, 0777, true);
    }

    $folderPath = realpath(dirname(__FILE__) . '/pdf_input/'.$folder_name);
    $files = new DirectoryIterator($folderPath);
 
    foreach ($files as $item) {
        if($countRotate == LIMIT_API) {
            exit;
        }

        $pdf_file_name = $item->getFilename();
        $origin_pdf = $item->getPathname();
        // $fileType = mime_content_type($item->getPathname());
        // $fileType = "application/pdf";
        $fileType = pathinfo($item->getPathname(), PATHINFO_EXTENSION);

        if ($item->isFile() && $fileType == "pdf") {
            $sourceFile = $item->getPathname();
            $rotateFile = $folderPath."/convert_pdf/rotate_".$pdf_file_name;

            // check window or ios
            $fileSize = filesize($sourceFile);
            if($fileSize > 200000) {
                // 1. Crop PDF if window
                cropPdfWindow($sourceFile, $rotateFile);
            }
            else {
                // 1. Rotate PDF if ios
                rotatePdf($sourceFile, $rotateFile);
            }

            $countRotate++;
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload PDF to server</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        *,
        *:before,
        *:after {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 2rem 1.5rem;
            font: 1rem/1.5 "PT Sans", Arial, sans-serif;
            color: #5a5a5a;
        }

       /* .file-custom {
            position: absolute;
            top: 0;
            right: 0;
            left: 0;
            z-index: 5;
            height: 2.5rem;
            padding: 0.5rem 1rem;
            line-height: 1.5;
            color: #555;
            background-color: #fff;
            border: 0.075rem solid #ddd;
            border-radius: 0.25rem;
            box-shadow: inset 0 0.2rem 0.4rem rgba(0,0,0,.05);
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        .file-custom:before {
            position: absolute;
            top: -0.075rem;
            right: -0.075rem;
            bottom: -0.075rem;
            z-index: 6;
            display: block;
            content: "Browse";
            height: 2.5rem;
            padding: 0.5rem 1rem;
            line-height: 1.5;
            color: #555;
            background-color: #eee;
            border: 0.075rem solid #ddd;
            border-radius: 0 0.25rem 0.25rem 0;
        }
        .file-custom:after {
            content: "Choose file...";
        }
        .file {
            position: relative;
            display: inline-block;
            cursor: pointer;
            height: 2.5rem;
            margin: 0 0 15px;
        }
        .file input {
            min-width: 14rem;
            margin: 0;
            filter: alpha(opacity=0);
            opacity: 0;
        }*/
        .btn-submit {
            display: block;
            height: 40px;
            background: #0c88b4;
            color: #fff;
            margin: 20px 0;
            padding: 0 20px;
            box-shadow: none;
            border: 1px solid #0c88b4;
            border-radius: 5px;
            font-size: 14px;
            text-transform: uppercase;
            text-align: center;
            cursor: pointer;
        }
        .error-message {
            color: red;
        }
        .success-message {
            color: #00a32a;
        }

        .file-item {
            display: block;
            float: left;
            margin-bottom: 20px;
            width: 100%;
        }
    </style>
</head>
<body>
<form action="upload_pdf.php"
      method="post"
      enctype="multipart/form-data">
    <div class="file-item">
        <label class="file">
            PDF files: <input  type="file" name="fileToUpload[]" id="fileToUpload" multiple>
            <span class="file-custom"></span>
        </label>
    </div>

    <div class="file-item">
        <label class="file">
            Fake address: <input type="file" name="fakeAddress" id="fakeAddress" />
        </label>
        <br/><span style="color: #ff0000;font-size: 14px;">Only Image type: .png</span>
    </div>


    <div class="success-message" id="progressContainer"><?php echo isset($message_success) && $message_success != "" ? "SUCCESS: <br>".$message_success : "" ?></div>
    <div class="error-message"><?php echo isset($message_error) && $message_error!="" ? "ERROR: <br>".$message_error : "" ?></div>
    <?php
    if( isset($folder_name) ):
        echo $countRotate." files have uploaded.<br/>";
    ?>
    <p><a target="_blank" href="<?=DOMAIN?>download_pdf.php?name=<?=$folder_name?>"><?=DOMAIN?>download_pdf.php?name=<?=$folder_name?></a></p>
    <?php endif; ?>
    <input id="uploadButton" type="submit" class="btn-submit" name="submit" value="CONVERT 4x6">
</form>
</body>
</html>
