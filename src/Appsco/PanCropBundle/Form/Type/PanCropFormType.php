<?php

namespace Appsco\PanCropBundle\Form\Type;

use Appsco\PanCropBundle\Form\DataTransformer\CropDataTransformer;
use Appsco\PanCropBundle\Image\Format;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

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
        //$options['file_options']['auto_initialize'] = false;
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
            'file' => $options['file_mapping'],
            'mime' => $options['mime_mapping'],
            'size' => $options['size_mapping'],
            'name' => $options['name_mapping'],
            'data' => $options['data_mapping'],
        ));

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($builder, $options) {
            $data = $event->getData();
            $this->transformer->setCropData(json_decode($data['crop_data'], true));
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'file_format' => Format::PNG,
            'file_mapping' => 'file',
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