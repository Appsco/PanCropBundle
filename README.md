PanCropBundle
=============

Bundle with form type for uploading cropped image. Based on https://github.com/igorpan/pan-crop

Usage
-----

Blob object
``` php
<?php

namespace AcmeBundle\Model;

class Blob
{
    /** @var int */
    protected $id;

    /** @var string */
    protected $title;

    /** @var int */
    protected $size;

    /** @var string */
    protected $contentType;

    /** @var resource */
    protected $content;
}

```


Entity with reference to Blob object
``` php
<?php

namespace AcmeBundle\Model;

class User
{
    /** @var string */
    protected $email;

    /** @var Blob|null */
    protected $picture;

    /** @var string|null */
    protected $pictureUrl;
}

```

Form type

``` php
<?php

namespace AcmeBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ProfileType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // PROFILE
        $builder
            ->add('email', 'email')
            ->add('picture_blob', 'pan_crop', array(
                'required' => false,
                'data_class' => 'AcmeBundle\Model\Blob',
                'data_mapping' => 'content',
                'mime_mapping' => 'contentType',
                'size_mapping' => 'size',
                'name_mapping' => 'title',
            ))
            ->add('save', 'submit', array(
                'label' => 'Save'
            ))
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'AcmeBundle\Model\User',
        ));
    }


    /**
     * Returns the name of this type.
     * @return string The name of this type
     */
    public function getName()
    {
        return 'profile';
    }

}

```


Controller
``` php
<?php

namespace AcmeBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    public function indexAction($userId, Request $request)
    {
        $user = $this->loadUser($userId);
        $form = $this->createForm('profile', $user);
        $form->handleRequest($request);
        if ($form->isValid()) {
            if ($blob = $user->getPicture()) {
                if ($blob->getContent()) {
                    $this->savePicture($blob);
                }
                if ($blob->getId()) {
                    $user->setPictureUrl(
                        $this->router->generate(
                            'picture_path',
                            array('blobId' => $blob->getId()),
                            UrlGeneratorInterface::ABSOLUTE_URL
                        )
                    );
                }
            }
            $this->saveUser($user);
        }

        return $this->render('@Acme/Default/index.html.twig', array(
            'user' => $user,
            'form' => $form->createView(),
        ));
    }
}
```


View

``` twig
{% extends '::base.html.twig' %}

{% block body %}
    <div id="img-preview-container">
        <img id="img-preview" src="{{ user.pictureUrl|default('http://placehold.it/200') }}?r={{ random() }}" />
    </div>

    {{ form(form) }}
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    {% javascripts
        '@AppscoPanCropBundle/Resources/public/js/jquery.pan-crop.min.js'
    %}
        <script src="{{ asset_url }}"></script>
    {% endjavascripts %}

    <script type="text/javascript">
        $('#profile_picture_file').panCropUi({
            $previewBox: $('#img-preview-container'), // the preview pane used for cropping
            submitCropData: '#profile_picture_crop_data' // matches the hidden field corresponded with the file input
        });
    </script>
{% endblock %}

```