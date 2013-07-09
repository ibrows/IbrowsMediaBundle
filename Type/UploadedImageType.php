<?php

namespace Ibrows\MediaBundle\Type;

use Symfony\Component\HttpFoundation\File\UploadedFile;

use Symfony\Component\HttpFoundation\File\File;

class UploadedImageType extends AbstractUploadedType
{
    /**
     * @var number
     */
    protected $maxSize;
    /**
     * @var number
     */
    protected $maxHeight;
    /**
     * @var number
     */
    protected $maxWidth;
    /**
     * @var array
     */
    protected $formats;
    /**
     * @var array
     */
    protected $mimeTypes;
    
    public function __construct($max_width, $max_height, $max_size, array $mime_types, array $formats)
    {
        $this->maxWidth =  $max_width;
        $this->maxHeight = $max_height;
        $this->maxSize = $max_size;
        
        $this->mimeTypes = $mime_types;
        $this->formats = $formats;
    }
    
    /**
     * @param File $file
     * @return boolean
     */
    protected function supportsMimeType(File $file)
    {
        if ($file instanceof UploadedFile) {
            $mime = $file->getClientMimeType();
        } else {
            $mime = $file->getMimeType();
        }
    
        return array_search($mime, $this->mimeTypes) !== false;
    }
    
    /**
     * {@inheritdoc}
     */
    public function validate($file)
    {
        /* @var $file File */
        $fileSizeError = $this->validateFileSize($file);
        if ($fileSizeError) {
            return $fileSizeError;
        }
        
        $imgSizeError = $this->validateImgSize($file);
        if ($imgSizeError) {
            return $imgSizeError;
        }
    }
    
    /**
     * 
     * @param File $file
     * @return void|string
     */
    protected function validateFileSize(File $file)
    {
        if (!$this->maxSize) {
            return;
        }
        
        $fileSize = $file->getSize();
        if ($fileSize > $this->maxSize) {
            return 'media.error.fileSize';
        }
    }

    /**
     * 
     * @param File $file
     * @return void|string
     */
    protected function validateImgSize(File $file)
    {
        if (!$this->maxHeight && !$this->maxWidth) {
            return;
        }
        
        $img = new \Imagick($file->getPathname());
        $height = $img->getimageheight();
        $width = $img->getimagewidth();
        
        if ($this->maxHeight && $height > $this->maxHeight) {
            return 'media.error.imageHeight';
        }
        
        if ($this->maxWidth && $width > $this->maxWidth) {
            return 'media.error.imageWidth';
        }
    }
    
    /**
     * {@inheritdoc}
     */
    protected function postRemoveExtra($extra)
    {
        if (is_array($extra)){
            foreach ($this->formats as $name => $format) { 
                if (array_key_exists($name, $extra)) {
                    $filename = $extra["{$name}_filename"];
                    if (file_exists($filename)) {
                        unlink($filename);
                    }
                }
            }
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function generateExtra($file)
    {
        $extra = parent::generateExtra($file);
        foreach ($this->formats as $name => $format) {
            $width = array_key_exists('width', $format) ? $format['width'] : null;
            $height = array_key_exists('height', $format) ? $format['height'] : null;
            
            $resizedFile = $this->resizeImage($file, $width, $height);
            $extra = array_merge($extra, array(
                    "{$name}_filename" => $resizedFile->getPathname(),
                    $name => $this->getWebUrl($resizedFile)
            ));
        }
        
        return $extra;
    }
    
    /**
     * @param \Symfony\Component\HttpFoundation\File\File $file
     * @param number|null $targetwidth
     * @param number|null $targetheight
     * 
     * @return \Symfony\Component\HttpFoundation\File\File
     */
    protected function resizeImage(File $file,  $targetwidth, $targetheight)
    {
        $targetfilename = $this->getWebDir($file).'/'.$this->getWebFilename($file);

        $img = new \Imagick($file->getPathname());
        $height = $img->getimageheight();
        $width = $img->getimagewidth();
        $factor = $height/$width;
        if (!$targetheight) {
            $targetheight = intval($targetwidth * $factor);
        }
        if (!$targetwidth) {
            $targetwidth = intval($targetheight / $factor);
        }
        
        $img->cropthumbnailimage($targetwidth, $targetheight);
        $img->writeimage($targetfilename);
        
        return new File($targetfilename);
    }
    
    public function getName()
    {
        return 'image';
    }
}
