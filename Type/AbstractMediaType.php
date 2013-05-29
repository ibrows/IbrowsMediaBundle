<?php

namespace Ibrows\MediaBundle\Type;

use Ibrows\MediaBundle\Model\MediaInterface;

abstract class AbstractMediaType implements MediaTypeInterface
{
    
    public function validate($data)
    {
        return null;
    }
    
    protected function preTransformData($data)
    {
        return $data;
    }
    
    protected function postTransformData($data)
    {
        return $data;
    }
    
    protected function generateExtra($data)
    {
        return null;
    }
    
    protected function generateUrl($data, $extra)
    {
        return null;
    }
    
    protected function generateHtml($data, $extra)
    {
        return null;
    }
    
    public function prePersist(MediaInterface $media)
    {
        $data = $this->preTransformData($media->getData());
        $extra = $this->generateExtra($data);
        
        $media->setExtra($extra);
        $media->setUrl($this->generateUrl($data, $extra));
        $media->setHtml($this->generateHtml($data, $extra));
        
        $data = $this->postTransformData($data);
        $media->setData($data);
    }
    
    public function preUpdate(MediaInterface $media, array $changeSet)
    {
        $olddata = $changeSet['data'][0];
        $newdata = $changeSet['data'][1];
        
        $media->setData($olddata);
        $this->postRemove($media);
        
        $media->setData($newdata);
        $this->prePersist($media);
    }
    
    public function postRemove(MediaInterface $media)
    {
    }
    
    public function postLoad(MediaInterface $media)
    {
    }
}