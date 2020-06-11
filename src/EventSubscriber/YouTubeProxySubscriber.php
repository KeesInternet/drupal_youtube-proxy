<?php
/**
 * @file
 * Contains \Drupal\youtube_proxy\EventSubscriber\MyEventSubscriber.
 */

namespace Drupal\youtube_proxy\EventSubscriber;

use Drupal;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
/**
 * Event Subscriber YouTubeProxySubscriber.
 */
class YouTubeProxySubscriber implements EventSubscriberInterface
{
    /**
     * Create video_id to retreive thumbnails for youtube videos
     *
     * @param FilterResponseEvent $event
     * @return JSON json response
     */
    public function onRespond()
    {
        if (isset($_GET['video_id']) && $_GET['video_id'] !== "") {
            // Create error array
            $error = [];
            $videoid = $_GET['video_id'];
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
                    case 'jpg':
                        header('Content-type: image/jpeg');
                        imagejpeg(imagecreatefromjpeg($img));
                        header("Content-disposition: inline; filename=thumbnail_$videoid.jpeg");
                        break;
                    case 'webp':
                        header('Content-type: image/webp');
                        header("Content-disposition: inline; filename=thumbnail_$videoid.webp");
                        imagewebp(imagecreatefromjpeg($img));
                        break;
                    case 'json':
                        header('Content-Type: application/json');
                        $response = [
                            'status' => 200
                        ];
                        echo json_encode($response);
                        exit;
                        break;

                    default:
                        header('Content-type: image/png');
                        header("Content-disposition: inline; filename=thumbnail_$videoid.png");
                        imagepng(imagecreatefromjpeg($img));
                        break;
                }
            } else {
                header('Content-type: image/png');
                header("Content-disposition: inline; filename=thumbnail_$videoid.png");
                imagepng(imagecreatefromjpeg($img));
            }

        }
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
    }

}
