<?php

namespace Drupal\youtube_proxy\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "youtube_rest_resource",
 *   label = @Translation("YouTube rest resource"),
 *   uri_paths = {
 *     "canonical" = "/api/yt_proxy/{video_id}"
 *   }
 * )
 */
class YouTubeRestResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
    protected $currentUser;
    private $_cacheId;

  
  /**
   * {@inheritdoc}
   */
    public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
        $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
        $instance->logger = $container->get('logger.factory')->get('youtube_proxy');
        $instance->currentUser = $container->get('current_user');
        $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
        return $instance;
    }

    /**
     * Responds to GET requests.
     *
     * @param string $payload
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function get($video_id = null) {
        // You must to implement the logic of your REST Resource here.
        // Use current user after pass authentication to validate access.
        if (!$this->currentUser->hasPermission('access content')) {
            throw new AccessDeniedHttpException();
        }

        // Check if the video ID is set.
        if ($video_id == null || !isset($video_id)) {
          // Throw exception no video id
            throw new UnprocessableEntityHttpException('No youtube ID.');
        }

        // Validate youtube id.
        $regex = "/^[0-9A-Za-z\s\-\_]+$/";
        if (strlen($video_id) !== 11 || !preg_match($regex, $video_id)) {
            throw new UnprocessableEntityHttpException('Not a valid youtube ID.');
        }

        // Set cache id.
        $this->_cacheId = "video_proxy:{$video_id}";

        // Generate the image from cache.
        if (\Drupal::cache()->get($this->_cacheId)) {
            $this->generateImage(\Drupal::cache()->get($this->_cacheId), true);
        }

        // Try a different quality sources and get the best.
        $source = $this->qualityCheck($video_id);

        if ($source == false) {
            throw new NotFoundHttpException("No thumbnail found");
        }

        return new ResourceResponse($this->generateImage($source), 200);
    }

    private function qualityCheck($video_id) {
        foreach (['maxresdefault.jpg', 'sddefault.jpg', '0.jpg'] as $quality) {
            if (getimagesize("https://img.youtube.com/vi/{$video_id}/{$quality}") !== false) {
                return "https://img.youtube.com/vi/{$video_id}/{$quality}";
            }
        }
        return false;
    }

    private function generateImage($source, $fromCache = false) {
        // Check if come from cache
        if ($fromCache == false) {
            // Get binary data.
            $imageFile = file_get_contents($source);
            if ($imageFile == false) {
                throw new UnprocessableEntityHttpException('Cannot process thumbnail.');
            }
            $base64 = 'data:image/jpeg;base64,' . base64_encode($imageFile);
        } else {
            $base64 = \Drupal::cache()->get($this->_cacheId)->data;
        }
        // Save the base64 in the cache.
        \Drupal::cache()->set($this->_cacheId, $base64);


        // Generate new image.
        $base64 = str_replace('data:image/jpeg;base64,', '', $base64);
        $binary = base64_decode($base64);

        $image = imagecreatefromstring($binary);

        header('Content-Type: image/jpeg');
        imagejpeg($image);
        imagedestroy($image);
    }

}
