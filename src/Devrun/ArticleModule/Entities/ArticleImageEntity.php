<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    Images.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Entities;

use Devrun\CmsModule\Entities\IImage;
use Devrun\Doctrine\Entities\ImageTrait;
use Doctrine\ORM\Mapping as ORM;
use Devrun\Doctrine\Entities\BlameableTrait;
use Devrun\Doctrine\Entities\DateTimeTrait;
use Devrun\Doctrine\Entities\IdentifiedEntityTrait;
use Kdyby\Doctrine\Entities\MagicAccessors;

/**
 * Class Images
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Entity
 * @ORM\Table(name="article_image")
 *
 * @package Devrun\CmsModule\Entities
 */
class ArticleImageEntity implements IImage
{
    use IdentifiedEntityTrait;
    use MagicAccessors;
    use DateTimeTrait;
    use BlameableTrait;
    use ImageTrait;

    /**
     * @var ArticleEntity
     * @ORM\OneToOne(targetEntity="ArticleEntity", inversedBy="image")
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