<?php

namespace Appsco\PanCropBundle\Image;

use Appsco\PanCropBundle\Error\InvalidImageFormatException;

class ImageCropper
{
    /**
     * @var resource
     */
    protected $image;

    /**
     * @var resource
     */
    protected $cropped;

    /**
     * @var array
     */
    private $formats = array(
        Format::PNG => 'imagepng',
        Format::JPG => 'imagejpeg',
    );

    /**
     * Loads image from string
     *
     * @param string $imageData
     * @throws \Appsco\PanCropBundle\Error\InvalidImageFormatException
     */
    public function loadImageFromString($imageData)
    {
        try {
            $this->image = imagecreatefromstring($imageData);
        } catch (\Exception $ex) {
            throw new InvalidImageFormatException();
        }
    }

    /**
     * Loads image from file
     *
     * @param string $filepath
     */
    public function loadImageFromFile($filepath)
    {
        $this->image = imagecreatefromstring(file_get_contents($filepath));
    }

    /**
     * Crops image
     *
     * @param int $sourceX      Source image top left X coordinate
     * @param int $sourceY      Source image top left Y coordinate
     * @param int $sourceWidth  Area width on source image
     * @param int $sourceHeight Area height on source image
     * @param int $targetWidth  Area width on destination image
     * @param int $targetHeight Area height on destination image
     *
     * @throws \LogicException
     */
    public function crop($sourceX, $sourceY, $sourceWidth, $sourceHeight, $targetWidth, $targetHeight)
    {
        if (null === $this->image) {
            throw new \LogicException(
                'You have to load an image before croping it. 
                Use loadImageFromString or loadImageFromFile'
            );
        }

        $destination = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled(
            $destination,
            $this->image,
            0,
            0,
            $sourceX,
            $sourceY,
            $targetWidth,
            $targetHeight,
            $sourceWidth,
            $sourceHeight
        );

        $this->cropped = $destination;
    }

    /**
     * Returns image as string
     *
     * @param string $format
     *
     * @return string
     *
     * @throws \LogicException
     */
    public function getCroppedImageAsString($format = Format::PNG)
    {
        if (null === $this->cropped) {
            throw new \LogicException('You have to crop image first before getting it');
        }

        return $this->formatImageData($this->cropped, $format);
    }

    /**
     * Saves image to a file
     *
     * @param string $filepath
     * @param string $format
     */
    public function saveCroppedImageToFile($filepath, $format = Format::PNG)
    {
        file_put_contents($filepath, $this->getCroppedImageAsString($format));
    }

    /**
     * Formats image resource data to chosen format.
     *
     * @param resource $data
     * @param string $format
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    private function formatImageData($data, $format)
    {
        if (false == isset($this->formats[$format])) {
            throw new \InvalidArgumentException(sprintf(
                'Unsupported format %s. Supported formats are: %s',
                $format,
                implode(', ', array_keys($this->formats))
            ));
        }

        ob_start();
        call_user_func($this->formats[$format], $data);
        $formatted = ob_get_clean();

        return $formatted;
    }
}