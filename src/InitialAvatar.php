<?php

namespace DgThanhDuy\PHPAvatar;

require_once __DIR__."/vendor/autoload.php";

use Intervention\Image\Gd\Font;
use Intervention\Image\Gd\Shapes\CircleShape;
use Intervention\Image\ImageManager;

class InitialAvatar
{
    /**
     * Image Type PNG
     */
    const MIME_TYPE_PNG = 'image/png';

    /**
     * Image Type JPEG
     */
    const MIME_TYPE_JPEG = 'image/jpeg';

    /**
     * @var string
     */
    private $name;


    /**
     * @var string
     */
    private $nameInitials;


    /**
     * @var string
     */
    private $shape;


    /**
     * @var int
     */
    private $size;

    /**
     * @var ImageManager
     */
    private $imageManager;

    /**
     * @var string
     */
    private $backgroundColor;

    /**
     * @var string
     */
    private $foregroundColor;

    /**
     * InitialAvatar constructor.
     * @param string $name
     * @param string $shape
     * @param int    $size
     * @param string $colorString
     * @param int    $length
     */
    public function __construct(
        string $name, 
        string $shape = 'circle', 
        int $size = 48, 
        string $colorString = null
    )
    {
        $this->setName($name);
        $this->setImageManager(new ImageManager());
        $this->setShape($shape);
        $this->setSize($size);
        $this->setColorString($colorString);
    }
    
    /**
     * @param string $name
     */
    private function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @param string $colorString
     */
    private function setColorString(?string $colorString)
    {
        $this->colorString = ($colorString == null) ? $this->name : $colorString;
    }

    /**
     * @param ImageManager $imageManager
     */
    private function setImageManager(ImageManager $imageManager)
    {
        $this->imageManager = $imageManager;
    }

    /**
     * @param string $shape
     */
    private function setShape(string $shape)
    {
        $this->shape = $shape;
    }


    /**
     * @param int $size
     */
    private function setSize(int $size)
    {
        $this->size = $size;
    }


    /**
     * @return \Intervention\Image\Image
     */
    private function generate(): \Intervention\Image\Image
    {
        $isCircle = $this->shape === 'circle';

        $this->nameInitials = $this->getInitials($this->name);
        $this->backgroundColor = $this->backgroundColor ?: $this->stringToColor($this->colorString);
        $this->foregroundColor = $this->foregroundColor ?: '#fafafa';

        $canvas = $this->imageManager->canvas(480, 480, $isCircle ? null : $this->backgroundColor);

        if ($isCircle) {
            $canvas->circle(480, 240, 240, function (CircleShape $draw) {
                $draw->background($this->backgroundColor);
            });
        }

        $canvas->text($this->nameInitials, 240, 240, function (Font $font) {
            $font->file(__DIR__ . '/fonts/arial-bold.ttf');
            $font->size(160);
            $font->color($this->foregroundColor);
            $font->valign('middle');
            $font->align('center');
        });

        return $canvas->resize($this->size, $this->size);
    }

    /**
     * @param string $name
     * @return string
     */
    private function getInitials(string $name): string
    {
        $letters = mb_substr($name, 0, 2);

        if (mb_strlen($name) > 2 && mb_strpos($name, " ") < mb_strlen($name)) {
            $letters = mb_substr($name, 0, 1) . mb_substr($name, mb_strrpos($name, " ") + 1, 1);
        } else {
            $letters = mb_substr($name, 0, 2);
        }
                
        $letters = implode('',mb_str_split($letters)); 
        return mb_strtoupper($letters);

    }

    /**
     *
     * @param string $mimetype
     * @param int    $quality
     * @return string
     */
    public function encode($mimetype = self::MIME_TYPE_PNG, $quality = 90): string
    {
        $allowedMimeTypes = [
            self::MIME_TYPE_PNG,
            self::MIME_TYPE_JPEG,
        ];
        if(!in_array($mimetype, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException('Invalid mimetype');
        }
        return $this->generate()->encode($mimetype, $quality);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->generate()->encode('data-url');
    }

    /**
     * @param string $string
     * @return string
     */
    private function stringToColor(string $string): string
    {

        $rgb = substr(dechex(crc32($string)), 0, 6);
        $darker = 1.5;
        list($R16, $G16, $B16) = str_split($rgb, 2);

        $R10 = hexdec($R16);
        $G10 = hexdec($G16);
        $B10 = hexdec($B16);

        $RGBArr = [
            'red' => $R10,
            'green' => $G10,
            'blue' => $B10
        ];

        arsort($RGBArr);
        $counter = 0;

        array_walk_recursive( $RGBArr, function(&$value, $key) use (&$counter) {
            $counter++;
            if($counter == 1) {
                $value += 50;
                if($value > 255) {
                    $value = 255;
                }
            }
            if($counter == 3) {
                $value -= 50;
                if($value < 0) {
                    $value = 0;
                }
            }
        }, $counter);

        $R = sprintf('%02X', floor($RGBArr['red'] / $darker + 20));
        $G = sprintf('%02X', floor($RGBArr['green'] / $darker + 20));
        $B = sprintf('%02X', floor($RGBArr['blue'] / $darker + 20));
        
        return '#' . $R . $G . $B;
    }

}
