# Kees-TM Youtube Proxy

Youtube Proxy is a Drupal 8 module to retreive youtube thumbnails without using the YouTube API.
This module will:

- Output the best quality youtube thumbnail available.
- Convert the thumbnail to jpeg|png|webp

## Installation

**On a drupal 8 project:**

***composer require keestm/youtube_proxy***
___

## Usage

Go to http://example.com/?videoid=[videoid] (default output: png if found, on error json)
Optional: 
&output=[jpeg|png|webp|json] Get different types of output

## License

MIT License

Copyright &copy; 2018 Kees™ Internetbureau

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.