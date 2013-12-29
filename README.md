iBROWS Media Bundle
========================

The IbrowsMediaBundle is a small bundle that helps you deal with your basic media and upload files.
It aims to be as minimalistic as possible but with the possibility to extend to your likes.
You can expect the following features of this bundle:

 * Handling of file uploads and removal
 * Image uploads with custom formats (generated during upload)
 * Easy extension with custom media types
 * Different media types depending on the user input (e.g. youtube, soundcloud link using the same form and entity)
 
If you want a more feature rich solution with admin management intagration, take a look at the [SonataMediaBundle](https://github.com/sonata-project/SonataMediaBundle).

## Installation

Add the following lines in your composer.json:

```
{
    "require": {
        "ibrows/media-bundle": "dev-master"
    }
}
```

For composer to find the bundle, additionally you need to add the following to the repositories section in your composer.json

```
"repositories": [
    {
        "type": "vcs",
        "url": "git@codebasehq.com:ibrows/ibrowsch/ibrows-media-bundle.git"
    }
],
```

To start using the bundle, register the bundle in your application's kernel class:

``` php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        // ...
        new Ibrows\MediaBundle\IbrowsMediaBundle(),
    );
)
```

## Configuration

### Entity
```
use Ibrows\MediaBundle\Model\Media as AbstractMedia;
use Doctrine\ORM\Mapping as ORM;

/**
 * Media
 *
 * @ORM\Table(name="media")
 * @ORM\Entity
 */
class Media extends AbstractMedia
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    protected $id;
    
    /**
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }
}
```

Now you can add the Media entity as a relation anywhere you want.


### Options
```
ibrows_media:
    template: "IbrowsMediaBundle:Media:blocks.html.twig"
    upload_location: "%kernel.root_dir%/../web"
    upload_root: /uploads

    enabled_types: [youtube, soundcloud, image, file]

    image:
        max_size: ~
        max_width: 1024
        max_height: ~
        
        mime_types: [image/jpeg, image/png]
        formats:
            small:
                height: 63
                width: 63
            medium:
                height: 72
                width: 72
            large:
                height: 300
                width: ~

    file:
        max_size: ~
        mime_types: ~ 
```
The sizes are in Kb and the width and height values are in pixels.

## Usage

### Form

Type guessing happens during form submit, hence it is important to use the built-in forms in order for the media persistence to work properly.

There are two available form types:

 * **ibrows_media_link**: offers a text field where you can submit a link (e.g. youtube link, soundcloud link)
 * **ibrows_media_upload**: offers a file field in order to upload a file

Each form definition needs the "data_class" attribute set in order to know which entity it should use to persist. 
The entity given should implement the MediaInterface.
Additionally there is a "type" option where you can define the supported types either as an array or a single string.

Below is an example usage of the different form types.

```
<?php

namespace Ibrows\MediaTestBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class MediaType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('image', 'ibrows_media_upload', array(
                'data_class' => 'Ibrows\MediaTestBundle\Entity\Media',
                'required' => false,
                'type' => 'image',
            ))
            ->add('upload', 'ibrows_media_upload', array(
                'data_class' => 'Ibrows\MediaTestBundle\Entity\Media',
                'required' => false,
            ))
            ->add('link', 'ibrows_media_link', array(
                'data_class' => 'Ibrows\MediaTestBundle\Entity\Media',
                'required' => false,
                'type' => array(
                    'youtube',
                    'soundcloud'
                ),
            ))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ibrows_media_test';
    }
}
```

### Types
Types are the concept where all the logic concerning the media is stored.
The basic interface consists of the following methods:

 * ***supports***: Whether or not the submitted data is supported by the type. The value returned represents the confidence in supporting the data. The higher the integer returned, the better the confidence. All internal types return confidence of 1, except "image" which returns 2 in order to beat the "file" type when both are used.
 * ***validate***: This method is used to validate the submitted data. You can either return a string error message, or a FormError instance, both of which will get translated using the validators translation domain.
 * ***prePersist***: Hook into the prePersist doctrine event.
 * ***postPersist***: Hook into the postPersist doctrine event.
 * ***preUpdate***: Hook into the preUpdate doctrine event.
 * ***postUpdate***: Hook into the postUpdate doctrine event.
 * ***postRemove***: Hook into the postRemove doctrine event.
 * ***postLoad***: Hook into the postLoad doctrine event.
 * ***getName***: Unique name to identify the type.

There also is an AbstractMediaType which offers convenience methods in order to simplify creation of custom types.

#### Built-in Types
There are several built-in types ready to use:

 * ***file***: A simple file upload type, which will take care of persistence and removal of uploaded files. You can limit the supported mime types and supported file size in the configuration.
 * ***image***: Apart from taking care of persistence and removal of your images, this type also offers the possibility to create other formats (e.g. thumbnails) during upload. The additional images can be accessed using the types *getExtraFile* or *getExtraFileUrl* methods.
 * ***youtube***: With this type you can add a youtube link either by full url or short url.
 * ***soundcloud***: With this type you can add a soundcloud link either by full url or short url.

#### Custom Types
In order to create a custom type you have to do three things:

 * Create the type
 * Add service definition
 * Define the template block

After that you can use the type through the form as you would any internal type.

##### Create the type
The main thing to do is to create the custom type logic.
Lets assume you want to create a PDF type, which will create a preview image of the uploaded PDF:

```
<?php

namespace Ibrows\MediaTestBundle\Media\Type;

use Ibrows\MediaBundle\Type\UploadedFileType;
use Symfony\Component\HttpFoundation\File\File;

class PdfType extends UploadedFileType
{

    /**
     * {@inheritdoc}
     */
    public function generateExtra($file)
    {
        $extra = parent::generateExtra($file);

        $preview = $this->generatePdfPreview($file);
        $this->addExtraFile($extra, 'preview', $preview);

        return $extra;
    }

    protected function generatePdfPreview(File $file)
    {
        $format = 'jpg';
        $im = new \Imagick();
        $im->readimage($file->getPathname() . '[0]');
        $im->setimagebackgroundcolor(new \ImagickPixel("white"));
        $im = $im->flattenimages();
        $im->setimageformat($format);

        $filename = tempnam(sys_get_temp_dir(), 'media_pdf_preview');
        $im->writeimage($filename);

        return new File($filename);
    }

    public function getName()
    {
        return 'pdf';
    }
}
```

Extending the AbstractMediaType or any of its inherited types allows you to use some convenience methods:

 * ***preTransformData***: Offers the possibility to transform the submitted data before the generate methods will be called during persistence.
 * ***postTransformData***: Offers the possibility to transform the submitted data after the generate methods will be called during persistence.
 * ***generateExtra***: Generate an array of additional information to be stored.
 * ***generateUrl***: Generate the url for the type.
 * ***generateHtml***: Generate the html for the type.

The AbstractUploadedType additionally offers the following methods:

 * ***addExtraFile***: Add an additional file to the entity. Moving, loading and removal of the file will be taken care of.
 * ***removeExtraFile***: Remove an additional file previously added through addExtraFile.
 * ***getExtraFile***: Get an additional file previously added through addExtraFile
 * ***getExtraFileUrl***: Get the generated url of an additional file previously added through addExtraFile

##### Add service definition
Next you need to add the service definition for your custom type:

```
        <service id="ibrows_media.type.pdf" class="Ibrows\MediaTestBundle\Media\Type\PdfType">
            <tag name="ibrows_media.type" alias="ibrows_media_pdf" />
            <argument>%ibrows_media.file.max_size%</argument>
            <argument type="collection">
                <argument>application/pdf</argument>
            </argument>
            <call method="setUploadLocation">
                <argument>%ibrows_media.upload_location%</argument>
            </call>
            <call method="setUploadRoot">
                <argument>%ibrows_media.upload_root%</argument>
            </call>
        </service>
```

The important thing is to include the "ibrows_media.type" tag.

##### Define the template block
Last you need to define the template block where you define the rendering of the type:

```
{% block pdf %}
{% set source = type.getExtraFileUrl(media, 'preview') %}
<div class="media pdf">
    <a href="{{ asset(media.url) }}">
        <img src="{{ asset(source) }}" alt="{{ media.extra.originalFilename }}" />
    </a>
</div>
{% endblock %}
```

The block needs to have the same name as returned by the types getName method.
You can define the blocks in the template given in the configuration.

### Type guessing
Now lets assume you do not only want to allow pdf file but also general file upload.
But you still want a preview to be generated for pdf files only.
This can be achieved using the type guessing feature.

Simply return a higher confidence for the pdf type, than for the file type.
Then the pdf media type will be used whenever it supports the submitted data.

```
class PdfType extends UploadedFileType
{
    // ------
    
    /**
     * {@inheritdoc}
     */
    public function supports($file)
    {
        $supports = parent::supports($file);
        if ($supports) {
            return 5;
        }

        return $supports;
    }
    
    // ------
}
```

### Templating
Each media type needs its own block inside the global media template.
The block is identified using the media type name as returned by the getName function.

### Migration
There also is a migration command, which lets you migrate all or a specific type to a given or guessed target type.

```
Usage:
 ibrows:media:migrate [-src|--source[="..."]] [-tgt|--target[="..."]] class

Arguments:
 class                 Which class you want to migrate

Options:
 --source (-src)       which media type you want to migrate. If this option is given,
                       only the source type will be migrated, else all types in the table
                       will be migrated
 --target (-tgt)       which media type you want to migrate to. If this option is given,
                       it will only be migrated to the target type if it does support the
                       original data, else the target type will be guessed again.
 --help (-h)           Display this help message.
 --quiet (-q)          Do not output any message.
 --verbose (-v|vv|vvv) Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
 --version (-V)        Display this application version.
 --ansi                Force ANSI output.
 --no-ansi             Disable ANSI output.
 --no-interaction (-n) Do not ask any interactive question.
 --shell (-s)          Launch the shell.
 --process-isolation   Launch commands from shell as a separate process.
 --env (-e)            The Environment name. (default: "dev")
 --no-debug            Switches off debug mode.

```
