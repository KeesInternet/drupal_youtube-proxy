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
 *     "canonical" = "/api/yt_proxy/{videoId}"
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
  /**
   * The cache id for the current video.
   *
   * @var \Drupalcache
   */
  private $cacheId;

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
   * @param string $videoId
   *   The current youtube video id.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get($videoId = NULL) {
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    // Check if the video ID is set.
    if (empty($videoId)) {
      // Throw exception no video id.
      throw new UnprocessableEntityHttpException('No youtube ID.');
    }

    // Validate youtube id.
    $regex = "/^[0-9A-Za-z\s\-\_]+$/";
    if (strlen($videoId) !== 11 || !preg_match($regex, $videoId)) {
      throw new UnprocessableEntityHttpException('Not a valid youtube ID.');
    }

    // Set cache id.
    $this->cacheId = "video_proxy:{$videoId}";

    // Generate the image from cache.
    if (\Drupal::cache()->get($this->cacheId)) {
      $this->generateImage(\Drupal::cache()->get($this->cacheId), TRUE);
    }

    // Try a different quality sources and get the best.
    $source = $this->qualityCheck($videoId);

    if ($source == FALSE) {
      throw new NotFoundHttpException("No thumbnail found");
    }

    return new ResourceResponse($this->generateImage($source), 200);
  }

  /**
   * Get the best quality thumbnail.
   *
   * This function will get the best quality
   * thumbnail when it can find something.
   *
   * @param string $video_id
   *   The youtube video id.
   */
  private function qualityCheck($video_id) {
    foreach (['maxresdefault.jpg', 'sddefault.jpg', '0.jpg'] as $quality) {
      if (getimagesize("https://img.youtube.com/vi/{$video_id}/{$quality}") !== FALSE) {
        return "https://img.youtube.com/vi/{$video_id}/{$quality}";
      }
    }
    return FALSE;
  }

  /**
   * This function will generate the actual image.
   *
   * @param string $source
   *   The source to the correct quality.
   * @param bool $fromCache
   *   To check if the image needs to be generated from the cache.
   */
  private function generateImage($source, $fromCache = FALSE) {
    // Check if come from cache.
    if ($fromCache == FALSE) {
      // Get binary data.
      $imageFile = file_get_contents($source);
      if ($imageFile == FALSE) {
        throw new UnprocessableEntityHttpException('Cannot process thumbnail.');
      }
      $base64 = 'data:image/jpeg;base64,' . base64_encode($imageFile);
    }
    else {
      $base64 = \Drupal::cache()->get($this->cacheId)->data;
    }
    // Save the base64 in the cache.
    \Drupal::cache()->set($this->cacheId, $base64);

    // Generate new image.
    $base64 = str_replace('data:image/jpeg;base64,', '', $base64);
    $binary = base64_decode($base64);

    $image = imagecreatefromstring($binary);

    header('Content-Type: image/jpeg');
    imagejpeg($image);
    imagedestroy($image);
  }

}
