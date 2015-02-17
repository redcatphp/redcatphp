<?php namespace Surikat\Exception;
class Upload extends \Surikat\Exception\Exception{
    public function __construct($code){
        parent::__construct($this->codeToMessage($code));
    }
    private function codeToMessage($code){
        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            case 'extension':
                $message = "Incorrect file extension";
                break;
            case 'type':
                $message = "Incorrect file mime type";
                break;
            case 'move_uploaded_file':
                $message = "Unable to write uploaded file in target directory";
            break;
            default:
				if(is_string($code))
					$message = "Upload error (custom): $code";
				else
					$message = "Unknown upload error code: $code";
            break;
        }
        return $message;
    }
}