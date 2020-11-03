# Youtube Proxy

Youtube Proxy is a Drupal 8/9 module to get youtube thumbnails without using the YouTube API.
This module will:

- Output the best quality youtube thumbnail available.
- Convert the thumbnail to jpeg|png|webp

## Installation

**On a drupal 8 and 9 projects:**

***composer require estdigital/youtube_proxy***
___

## Usage

Go to http://example.com/?videoid=[videoid] (default output: png if found, on error json)
Optional: 
&output=[jpeg|png|webp|json] Get different types of output

## License

See LICENCE.md