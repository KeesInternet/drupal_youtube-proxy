<?php
namespace Drupal\kees_youtube_thumbnails;

use \Drupal\image\Entity\ImageStyle;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Base64Image
{
    protected $base64Image;
    protected $fileData;
    protected $fileName;
    protected $directory;

    public function __construct($base64Image, $videoid)
    {
        $this->base64Image = $base64Image;
        $this->decodeBase64Image($videoid);
    }

    protected function decodeBase64Image($videoid)
    {
        $this->fileData = \base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $this->base64Image));
        if ($this->fileData === false) {
            throw new BadRequestHttpException('Thumbnail cannot be processed');
        }
        // Determine image type
        $f = finfo_open();
        $mimeType = \finfo_buffer($f, $this->fileData, FILEINFO_MIME_TYPE);
        // Generate filename
        $ext = $this->getMimeTypeExtension($mimeType);
        $this->fileName = $videoid . $ext;
    }

    protected function getMimeTypeExtension($mimeType)
    {
        $mimeTypes = [
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            'image/gif' => 'gif',
            'image/bmp' => 'bmp',
            'image/vnd.microsoft.icon' => 'ico',
            'image/tiff' => 'tiff',
            'image/svg+xml' => 'svg',
        ];
        if (isset($mimeTypes[$mimeType])) {
            return '.' . $mimeTypes[$mimeType];
        } else {
            $split = explode('/', $mimeType);
            return '.' . $split[1];
        }
    }

    public function getFileData() {
        return $this->fileData;
    }

    public function getFileName() {
        return $this->fileName;
    }

    public function setFileDirectory($path) {
        $this->directory = \Drupal::service('file_system')->realpath(file_default_scheme() . "://");
        $this->directory .= '/'.$path;
        file_prepare_directory($this->directory, FILE_MODIFY_PERMISSIONS | FILE_CREATE_DIRECTORY);
    }

    // public function setImageStyleImages($path) {
    //     $styles = ['', '', ''];
    //     foreach ($styles as $style) {
    //         $imageStyle = ImageStyle::load($style);
    //         $uri = $imageStyle->buildUri($path . '/' . $this->fileName);
    //         $imageStyle->createDerivative($this->directory . '/' . $this->fileName, $uri);
    //     }
    // }
}
