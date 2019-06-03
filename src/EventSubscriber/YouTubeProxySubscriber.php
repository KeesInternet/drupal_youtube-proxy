<?php
/**
 * @file
 * Contains \Drupal\kees_youtube_proxy\EventSubscriber\YouTubeProxySubscriber.
 */

namespace Drupal\kees_youtube_proxy\EventSubscriber;

use Drupal;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
/**
 * Event Subscriber MyEventSubscriber.
 */
class YouTubeProxySubscriber implements EventSubscriberInterface
{
    /**
     * Create video_id to retreive thumbnails for youtube videos
     *
     * @param FilterResponseEvent $event
     * @return JSON json response
     */
    public function onRespond(FilterResponseEvent $event)
    {
        if (isset($_GET['video_id']) && $_GET['video_id'] !== "") {
            // Create error array
            $error = [];
            $videoid = $_GET['video_id'];

            if ($_GET['cache_clear'] && $_GET['cache_clear'] == true) {
                \Drupal::cache()->delete($videoid);
            }
            // \Drupal::cache()->set($videoid, 'test', time() + 300);
            // \Drupal::cache()->delete($videoid);
            // Check if the image is in cache
            $cache = \Drupal::cache()->get($videoid);

            if ($cache && !empty($cache)) {
                if ($cache->data['type'] !== 'json') {
                    switch ($cache->data['type']) {
                        case 'jpeg':
                            $this->outputJpeg($cache->data['img'], true);
                            break;
                        case 'webp':
                            $this->outputWebP($cache->data['img'], true);
                            break;
                        case 'png':
                            $this->outputPng($cache->data['img'], true);
                            break;
                    }
                } else {
                    $this->createJson();
                }

            }

            // Check if the right libraries are installed / enabled
            if (!extension_loaded('gd')) {
                $error['message'] = "PHP GD is not installed or enabled";
            }

            // Check for valid youtube id
            // Youtube id's are always 11 characters
            if (strlen($videoid) !== 11) {
                $error['message'] = "Video ID is not the right length. (invalid)";
            }

            // Check if string is build from valid characters
            $regex = "/^[0-9A-Za-z\s\-\_]+$/";
            if (!preg_match($regex, $videoid)) {
                $error['message'] = "Video ID contains invalid characters. (invalid)";
            }

            if ($error) {
                $this->displayJsonError($error);
                exit;
            }

            // Create the youtube url
            $baseUrl = "https://img.youtube.com/vi/".$videoid."/";
            $quality = [
                'hq' => 'maxresdefault.jpg',
                'sd' => 'sddefault.jpg',
                'min' => '0.jpg'
            ];

            $img = null;
            foreach ($quality as $key => $value) {
                $imgSrc = $baseUrl.$value;
                if (getimagesize($imgSrc) !== false) {
                    $img = $imgSrc;
                    break;
                }
            }

            if ($img == null) {
                $error['message'] = "here is no thumbnail available.";
                $error['fallback'] = true;
            }

            if ($error) {
                $this->displayJsonError($error);
                exit;
            }

            $allowedFormats = [
                'jpeg',
                'png',
                'webp',
                'json'
            ];

            if (isset($_GET['output']) && in_array($_GET['output'], $allowedFormats)) {
                switch ($_GET['output']) {
                    case 'jpeg':
                        $this->createJpeg($videoid, $img);
                        break;
                    case 'webp':
                        $this->createWebP($videoid, $img);
                        break;
                    case 'png':
                        $this->createPng($videoid, $img);
                        break;
                    case 'json':
                        $this->createJson();
                        break;
                    default:
                        $this->createPng($videoid, $img);
                        break;
                }
            } else {
                $this->createPng($videoid, $img);
            }

        }
    }

    private function createPng($videoid, $img)
    {
        ob_start();
        imagepng(imagecreatefromjpeg($img));
        $generatedImage = ob_get_contents();
        ob_end_clean();

        $this->saveImageToCache($videoid, 'png', $generatedImage);

        $this->outputPng($generatedImage);
    }

    private function outputPng($img, $cache = false)
    {
        if ($cache == true) {
            // echo 'data:image/png;base64,'.base64_encode($img);
            header('Content-type: image/png');
            echo $img;
        } else{
            header('Content-type: image/png');
            echo $img;
        }

        exit;
    }

    private function createJpeg($videoid, $img)
    {
        ob_start();
        imagejpeg(imagecreatefromjpeg($img));
        $generatedImage = ob_get_contents();
        ob_end_clean();

        $this->saveImageToCache($videoid, 'jpeg', $generatedImage);

        $this->outputJpeg($generatedImage);
    }

    private function outputJpeg($img)
    {
        header('Content-type: image/jpeg');
        echo $img;
        exit;
    }

    private function createWebP($videoid, $img)
    {
        ob_start();
        imagewebp(imagecreatefromjpeg($img));
        $generatedImage = ob_get_contents();
        ob_end_clean();

        $this->saveImageToCache($videoid, 'webp', $generatedImage);

        $this->outputWebP($generatedImage);
    }

    private function outputWebP($img)
    {
        header('Content-type: image/webp');
        echo $img;
        exit;
    }

    private function createJson()
    {
        header('Content-Type: application/json');
        $response = [
            'status' => 200
        ];
        echo json_encode($response);
        exit;
    }

    public static function getSubscribedEvents()
    {
        $events[KernelEvents::RESPONSE][] = ['onRespond'];
        return $events;
    }

    public function displayJsonError($errors)
    {
        // Set the content type to json
        header('Content-Type: application/json');
        // Encode the error data
        $errors['status'] = 404;
        echo json_encode($errors);
        exit;
    }

    public function saveImageToCache($videoId, $type, $imgData)
    {
        $data = [
            'type' => $type,
            'img' => $imgData
        ];

        \Drupal::cache()->set($videoId, $data, time() + 3600);
    }

}
