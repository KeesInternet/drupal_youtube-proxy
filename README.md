# Youtube Proxy

Youtube Proxy is a Drupal 8/9 module to get youtube thumbnails without using the YouTube API.
This module will:

- Output the best quality youtube thumbnail available.
- Store the thumbnail binary in cache, to reduce request time

## Installation

**On a drupal 8 and 9 projects:**

***composer require estdigital/youtube_proxy***
___

## Usage

Video id can be requested by going to:  
https://example.com/api/yt_proxy/`[video_id]`  

Errors will be returned in JSON

## License

See LICENCE.md