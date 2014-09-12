<?php

namespace Appsco\PanCropBundle\Form\DataTransformer;

use Appsco\PanCropBundle\Image\Format;
use Appsco\PanCropBundle\Image\ImageCropper;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

class CropDataTransformer implements DataTransformerInterface
{
    /**
     * @var PropertyAccessor
     */
    private $accessor;

    /**
     * @var ImageCropper
     */
    private $cropper;

    /**
     * @var string
     */
    private $fileFormat = Format::PNG;

    public function __construct()
    {
        $this->accessor = PropertyAccess::createPropertyAccessor();
        $this->cropper = new ImageCropper();
    }

    /**
     * @var array|null Deserialized data received from pan-crop plugin. Null if no crop data is received.
     *         Structure:
     *         {
     *             s : float   (scale)
     *             w : integer (width (if croping after scale))
     *             h : integer (height (if croping after scale))
     *             sw: integer (width (if croping before scale))
     *             sh: integer (height (if croping before scale))
     *             x : integer (x coordinate of top left corner (if croping after scale))
     *             y : integer (y coordinate of top left corner (if croping after scale))
     *             sx: integer (x coordinate of top left corner (if croping before scale))
     *             sy: integer (y coordinate of top left corner (if croping before scale))
     *         }
     */
    protected $cropData;

    /**
     * Contains mappings for: file (required), data (required), mime (optional), name (optional), size(optional)
     *
     * @var array
     */
    private $propertyMappings = [];

    /**
     * @param string $cropData
     * @return $this|CropDataTransformer
     */
    public function setCropData($cropData)
    {
        $this->cropData = $cropData;

        return $this;
    }

    /**
     * @param array $propertyPaths
     * @return $this|CropDataTransformer
     */
    public function setPropertyPaths(array $propertyPaths)
    {
        $this->propertyMappings = $propertyPaths;

        return $this;
    }

    /**
     * @param object $model
     * @return UploadedFile|null
     */
    protected function getFile($model)
    {
        if (false == isset($this->propertyMappings['file'])) {
            return null;
        }
        return $this->accessor->getValue($model, $this->propertyMappings['file']);
    }

    /**
     * Sets MIME property on transformed object
     *
     * @param string $mime
     * @param object $model
     * @return $this|CropDataTransformer
     */
    protected function setMime($mime, $model)
    {
        if ($this->propertyMappings['mime']) {
            $this->accessor->setValue($model, $this->propertyMappings['mime'], $mime);
        }

        return $this;
    }

    /**
     * Sets NAME property on transformed object
     *
     * @param string $name
     * @param object $model
     * @return $this|CropDataTransformer
     */
    protected function setName($name, $model)
    {
        if ($this->propertyMappings['name']) {
            $this->accessor->setValue($model, $this->propertyMappings['name'], $name);
        }

        return $this;
    }

    /**
     * Sets SIZE property on transformed object
     *
     * @param string $size
     * @param object $model
     * @return $this|CropDataTransformer
     */
    protected function setSize($size, $model)
    {
        if ($this->propertyMappings['size']) {
            $this->accessor->setValue($model, $this->propertyMappings['size'], $size);
        }

        return $this;
    }

    /**
     * Sets DATA property on transformed object
     *
     * @param string $data
     * @param object $model
     * @return $this|CropDataTransformer
     */
    protected function setData($data, $model)
    {
        $this->accessor->setValue($model, $this->propertyMappings['data'], $data);

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseTransform($model)
    {
        if ('' === $model || null === $model) {
            return null;
        }

        if ($this->getFile($model)) {
            $transformed = $this->cropImage($model);
        }

        return isset($transformed) ? $transformed : $model;
    }

    /**
     * @param object $model
     * @throws \LogicException
     * @return null|object
     */
    protected function cropImage($model)
    {
        $uploadedFile = $this->getFile($model);
        if (false == $uploadedFile) {
            throw new \LogicException('Expected uploaded file but got null');
        }

        $fileHandle = fopen($uploadedFile->getRealPath(), 'rb');
        $imageData = stream_get_contents($fileHandle);
        $imageMime = $uploadedFile->getMimeType();

        if (null !== $this->cropData) {
            $this->cropper->loadImageFromString($imageData);
            $this->cropper->crop(
                $this->cropData['sx'],
                $this->cropData['sy'],
                $this->cropData['sw'],
                $this->cropData['sh'],
                $this->cropData['w'],
                $this->cropData['h']
            );
            $imageData = $this->cropper->getCroppedImageAsString($this->fileFormat);
            $imageMime = 'image/png';
        }

        $this->setMime($imageMime, $model);
        $this->setSize(strlen($imageData), $model);
        $this->setName($uploadedFile->getClientOriginalName(), $model);
        $this->setData($imageData, $model);
    }

}