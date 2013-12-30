<?php

namespace Ibrows\MediaBundle\Twig\Extension;

use Ibrows\MediaBundle\Model\MediaInterface;
use Ibrows\MediaBundle\Exception\BlockNotDefinedException;
use Ibrows\MediaBundle\Manager\MediaTypeManager;

class CoreExtension extends \Twig_Extension
{

    /**
     *
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     *
     * @var \Twig_Template
     */
    protected $template;

    /**
     *
     * @var string
     */
    protected $templateName;

    /**
     *
     * @var MediaTypeManager
     */
    protected $manager;

    public function __construct($template, MediaTypeManager $manager)
    {
        $this->templateName = $template;
        $this->manager = $manager;
    }

    public function initRuntime(\Twig_Environment $environment)
    {
        $this->twig = $environment;
        parent::initRuntime($environment);
    }

    /**
     * (non-PHPdoc)
     *
     * @see Twig_Extension::getFunctions()
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('ibrows_media', array(
                $this,
                'renderMedia'
            ), array(
                'is_safe' => array(
                    'html'
                )
            ))
        );
    }

    public function renderMedia(MediaInterface $media, array $params = array())
    {
        if (! $this->template) {
            $this->template = $this->twig->loadTemplate($this->templateName);
        }
        $typename = $media->getType();
        // TODO: check for block existence
        // has block does not check parent templates
        /*
        if (! $this->template->hasBlock($typename)) {
            throw new BlockNotDefinedException($media, $this->templateName);
        }
        */

        return $this->template->renderBlock($typename, array(
            'media' => $media,
            'type' => $this->manager->getType($typename),
            'params' => $params
        ));
    }

    public function getName()
    {
        return 'ibrows_media_extension_core';
    }
}
