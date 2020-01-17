<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    Images.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Entities;

use Devrun\CmsModule\Entities\BlameableTrait;
use Devrun\CmsModule\Entities\IImage;
use Devrun\DoctrineModule\Entities\DateTimeTrait;
use Devrun\DoctrineModule\Entities\IdentifiedEntityTrait;
use Devrun\DoctrineModule\Entities\ImageTrait;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\MagicAccessors;

/**
 * Class Images
 * @ORM\Entity
 * @ORM\Table(name="article_images")
 *
 * @package Devrun\CmsModule\Entities
 */
class ArticleImagesEntity implements IImage
{
    use IdentifiedEntityTrait;
    use MagicAccessors;
    use DateTimeTrait;
    use BlameableTrait;
    use ImageTrait;


    /**
     * @var ArticleEntity
     * @ORM\ManyToOne(targetEntity="ArticleEntity", inversedBy="images")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $article;


    /**
     * @var string
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;


    /**
     * @param string $name
     *
     * @return $this
     */
    public function setReferenceIdentifier(string $name)
    {
        // TODO: Implement setReferenceIdentifier() method.
    }

    /**
     * @return string
     */
    public function getReferenceIdentifier()
    {
        // TODO: Implement getReferenceIdentifier() method.
    }


}