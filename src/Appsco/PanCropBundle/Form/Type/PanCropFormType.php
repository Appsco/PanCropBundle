<?php

namespace Appsco\PanCropBundle\Form\Type;

use Appsco\PanCropBundle\Form\DataTransformer\CropDataTransformer;
use Appsco\PanCropBundle\Image\Format;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class PanCropFormType extends AbstractType
{
    /**
     * @var DataTransformerInterface
     */
    private $transformer;

    public function __construct(CropDataTransformer $transformer)
    {
        $this->transformer= $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['file_options']['mapped'] = false;
        $builder
            ->add('file', 'file', $options['file_options'])
            ->add('crop_data', 'hidden', array(
                'mapped' => false,
                'data' => '{}'
            ))
        ;

        //$builder->addModelTransformer($this->transformer);
        $builder->addViewTransformer($this->transformer);
        $this->transformer->setPropertyPaths(array(
            'mime' => $options['mime_mapping'],
            'size' => $options['size_mapping'],
            'name' => $options['name_mapping'],
            'data' => $options['data_mapping'],
        ));

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($builder, $options) {
            $data = $event->getData();
            $this->transformer->setCropData(json_decode($data['crop_data'], true));
            if($data['file'] instanceof UploadedFile)
            {
                $this->transformer->setUploadedFile($data['file']);
            }
        });
    }

    public function setDefaultOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'file_format' => Format::PNG,
            'mime_mapping' => null,
            'size_mapping' => null,
            'name_mapping' => null,
            'crop_data_field' => 'cropData',
            'crop_scale' => 1,
            'file_options' => array('label'=>false)
        ));

        $resolver->setRequired(['data_mapping']);
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'pan_crop';
    }
}