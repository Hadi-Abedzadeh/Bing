<?php
error_reporting(0);
/**
 * A simple class which fetches Bing's image of the day with meta data
 */
class BingPhoto
{
    // Constants
    const TOMORROW = -1;
    const TODAY = 0;
    const YESTERDAY = 1;
    const LIMIT_N = 8; // Bing's API returns at most 8 images
    const RESOLUTION_LOW = '1366x768';
    const RESOLUTION_HIGH = '1920x1080';
    // API
    const BASE_URL = 'http://www.bing.com';
    const JSON_URL = '/HPImageArchive.aspx?format=js';
    private $args;
    private $data;
    /**
     * Constructor: Fetches image(s) of the day from Bing
     * @param int $date Date offset. 0 equals today, 1 = yesterday, and so on.
     * @param int $n Number of images / days
     * @param string $locale Localization string (en-US, de-DE, ...)
     * @param string $resolution Resolution of images(s)
     */
    public function __construct($date = self::TODAY, $n = 1, $locale = 'en-US', $resolution = self::RESOLUTION_HIGH)
    {
        $this->setArgs([
            'n' => $n,
            'date' => $date,
            'locale' => $locale,
            'resolution' => $resolution
        ]);
        try {
            $this->fetchImages();
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
    /**
     * Returns exactly one fetched image
     * @return array The image array with its URL and further meta data
     */
    public function getImage()
    {
        $images = $this->getImages(1);
        return $images[0];
    }
    /**
     * Returns n fetched images
     * @param int $n Number of images to return
     * @return array Image data
     */
    public function getImages($n = 1)
    {
        $n = max($n, count($this->data));
        return array_slice($this->data, 0, $n);
    }
    /**
     * Returns the class arguments
     * @return array Class arguments
     */
    public function getArgs()
    {
        return $this->args;
    }
    /**
     * Sets the class arguments
     * @param array $args
     */
    public function setArgs($args = [])
    {
        $defaults = [
            'n' => 1,
            'locale' => 'en-US',
            'date' => self::TODAY,
            'resolution' => self::RESOLUTION_HIGH
        ];
        $this->args = array_replace($defaults, $args);
        $this->validateArgs();
        try {
            $this->fetchImages();
        } catch (Exception $e) {
            exit($e->getMessage());
        }
    }
    /**
     * Perform some sanity checks
     */
    private function validateArgs()
    {
        $this->args['date'] = max($this->args['date'], self::TOMORROW);
        $this->args['n'] = min(max($this->args['n'], 1), self::LIMIT_N);
        if (false === in_array($this->args['resolution'], [self::RESOLUTION_LOW, self::RESOLUTION_HIGH])) {
            $this->args['resolution'] = self::RESOLUTION_HIGH;
        }
    }
    /**
     * Fetches the image JSON data from Bing
     * @throws Exception
     */
    private function fetchImages()
    {
        // Constructing API URL
        $fstring = self::BASE_URL . self::JSON_URL . '&idx=%s&n=%s&mkt=%s';
        $url = sprintf($fstring, $this->args['date'], $this->args['n'], $this->args['locale']);
        try {
            $this->data = $this->fetchJSON($url);
            $this->data = $this->setQuality($this->data['images']);
        } catch (Exception $e) {
            throw $e;
        }
    }
    /**
     * Fetches an associative array from given JSON url
     * @param  string $url JSON URL
     * @return array Associative data array
     * @throws Exception
     */
    private function fetchJSON($url)
    {
        $data = json_decode(file_get_contents($url), true);
        $error = json_last_error();
        if ($data !== null && $error === JSON_ERROR_NONE) {
            return $data;
        } else {
            throw new Exception('Unable to retrieve JSON data: ' . $error);
        }
    }
    /**
     * Sets the image resolution
     * @param array $images Array with image data
     * @return array Modified image data array
     */
    private function setQuality($images)
    {
        foreach ($images as $i => $image) {
            $url = str_replace(self::RESOLUTION_HIGH, $this->args['resolution'], $image['url']);
            $images[$i]['url'] = self::BASE_URL . $url;
        }
        return $images;
    }
}

class BingPhotoTest{
    /**
     * @var BingPhoto
     */
    protected $bing;
    protected function setUp()
    {
        $this->bing = new BingPhoto();
    }
    /**
     * @dataProvider invalidArgumentProvider
     * @param $expected
     * @param $args
     */
    public function testArgsValidation($expected, $args = [])
    {
        $this->bing->setArgs($args);
        $actual = $this->bing->getArgs();
        foreach ($expected as $key => $expectedArg) {
            $this->assertEquals($expectedArg, $actual[$key]);
        }
    }
    /**
     * @dataProvider argumentProvider
     * @param $args
     */
    public function testCount($args = [])
    {
        $this->bing->setArgs($args);
        $count = isset($args['n']) ? $args['n'] : 1;
        $this->assertCount($count, $this->bing->getImages());
    }
    /**
     * @dataProvider argumentProvider
     * @param $args
     */
    public function testResolution($args = [])
    {
        $this->bing->setArgs($args);
        foreach ($this->bing->getImages() as $image) {
            if (isset($args['resolution'])) {
                list($width, $height) = getimagesize($image['url']);
                $this->assertEquals($width . 'x' . $height, $args['resolution']);
            }
        }
    }
    public function invalidArgumentProvider()
    {
        return [
            'invalid date in future' => [
                [
                    'date' => -1
                ],
                [
                    'date' => -2
                ]
            ],
            'n too large' => [
                [
                    'n' => 8
                ],
                [
                    'n' => 9
                ]
            ],
            'n zero' => [
                [
                    'n' => 1
                ],
                [
                    'n' => 0
                ]
            ],
            'n negative' => [
                [
                    'n' => 1
                ],
                [
                    'n' => -2
                ]
            ],
            'unavailable resolution' => [
                [
                    'resolution' => '1920x1080'
                ],
                [
                    'resolution' => '1920x1200'
                ]
            ],
            'invalid resolution' => [
                [
                    'resolution' => '1920x1080'
                ],
                [
                    'resolution' => 'foo'
                ]
            ],
        ];
    }
    public function argumentProvider()
    {
        return [
            'no arguments' => [],
            'low resolution, de-DE' => [
                [
                    'resolution' => BingPhoto::RESOLUTION_LOW,
                    'locale' => 'de-DE'
                ]
            ],
            'high resolution, fr-FR' => [
                [
                    'resolution' => BingPhoto::RESOLUTION_HIGH,
                    'locale' => 'fr-FR'
                ]
            ],
            'three last images' => [
                [
                    'n' => 3
                ]
            ],
            'yesterday\'s image' => [
                [
                    'n' => 1,
                    'date' => BingPhoto::YESTERDAY
                ]
            ],
        ];
    }
}
if(!file_exists("C:/BING/")){
MKDIR("C:/BING/");
}
if(!file_exists("C:/BING/".date('Ymd').".png")){
$bing  = new BingPhoto(BingPhoto::TODAY, 1, BingPhoto::RESOLUTION_HIGH);
	foreach ($bing->getImages() as $image) 
		file_put_contents("C:/BING/".$image['startdate'].".png", file_get_contents($image['url']));		
}