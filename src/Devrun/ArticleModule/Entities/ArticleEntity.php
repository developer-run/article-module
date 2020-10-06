<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    ArticleEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Entities;

use Devrun;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\DoctrineModule\Entities\IdentifiedEntityTrait;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\MagicAccessors\MagicAccessors;
use Kdyby\Translation\Translator;
use Nette\Utils\DateTime;
use Zenify\DoctrineBehaviors\Entities\Attributes\Translatable as ZenifyTranslatable;

/**
 * Class ArticleEntity
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 * @ORM\Entity(repositoryClass="Devrun\ArticleModule\Repositories\ArticleRepository")
 * @ORM\Table(name="article", indexes={
 *  @ORM\Index(name="article_public_idx", columns={"public"}),
 *  @ORM\Index(name="published_from_to_idx", columns={"published_from", "published_to"}),
 *  @ORM\Index(name="position_idx", columns={"position"}),
 * })
 *
 * @package Devrun\ArticleModule\Entities
 * @method setPublic(bool $public)
 * @method ArticleTranslationEntity translate($lang = '', $fallbackToDefault = true)
 */
class ArticleEntity
{
    use MagicAccessors;
    use IdentifiedEntityTrait;
    use Devrun\DoctrineModule\Entities\DateTimeTrait;
    use Devrun\CmsModule\Entities\BlameableTrait;
    use Devrun\DoctrineModule\Entities\Attributes\Translatable;
//    use ZenifyTranslatable;



    /**
     * není vyřešeno mazání sekcí článků !
     *
     * @var PageEntity
     * @ORM\ManyToMany(targetEntity="Devrun\CmsModule\Entities\PageEntity")
     * @ORM\JoinTable(name="article_pages", joinColumns={@ORM\JoinColumn(onDelete="RESTRICT")}, inverseJoinColumns={@ORM\JoinColumn(onDelete="RESTRICT")}  )
     */
    protected $pages;

    /**
     * @var PackageEntity
     * @ORM\ManyToOne(targetEntity="Devrun\CmsModule\Entities\PackageEntity")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $package;

    /**
     * @var PageEntity
     * @ORM\ManyToOne(targetEntity="Devrun\CmsModule\Entities\PageEntity")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $page;

    /**
     * není vyřešeno mazání sekcí článků !
     *
     * @var Devrun\CmsModule\Entities\RouteEntity
     * @ORM\ManyToOne(targetEntity="Devrun\CmsModule\Entities\RouteEntity")
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $route;

    /**
     * není vyřešeno mazání sekcí článků !
     *
     * @var Devrun\CmsModule\Entities\PageSectionsEntity
     * @ORM\ManyToMany(targetEntity="Devrun\CmsModule\Entities\PageSectionsEntity")
     * @ORM\JoinTable(name="article_sections", joinColumns={@ORM\JoinColumn(onDelete="RESTRICT")},inverseJoinColumns={@ORM\JoinColumn(onDelete="RESTRICT")}  )
     */
    protected $pagesSections;

    /**
     * @var ArticleImageEntity
     * @ORM\OneToOne(targetEntity="ArticleImageEntity", mappedBy="article", orphanRemoval=true)
     */
    protected $image;

    /**
     * @var ArticleImagesEntity
     * @ORM\OneToMany(targetEntity="ArticleImagesEntity", mappedBy="article", orphanRemoval=true)
     */
    protected $images;

    /**
     * @var ArticleIdentifyEntity
     * @ORM\ManyToOne(targetEntity="ArticleIdentifyEntity", inversedBy="articles", cascade={"persist"})
     * @ORM\JoinColumn(onDelete="CASCADE")
     */
    protected $identify;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default": false})
     */
    protected $public = false;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $publishedFrom;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $publishedTo;

    /**
     * @var int
     * @ORM\Column(type="smallint", options={"default": 0})
     */
    protected $position = 0;



    /**
     * ArticleEntity constructor.
     */
    public function __construct(Translator $translator, ArticleIdentifyEntity $articleIdentifyEntity)
    {
        $this->pages         = new ArrayCollection();
        $this->images        = new ArrayCollection();
        $this->pagesSections = new ArrayCollection();

        $this->setDefaultLocale($translator->getDefaultLocale());
        $this->setCurrentLocale($translator->getLocale());

        $this->identify = $articleIdentifyEntity;
    }


    /**
     * @return ArticleIdentifyEntity
     */
    public function getIdentify(): ArticleIdentifyEntity
    {
        return $this->identify;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->getIdentify()->getNamespace();
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->getIdentify()->getIdentifier();
    }

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->translate()->getHeader();

    }

    /**
     * @param string $header
     */
    public function setHeader($header)
    {
        $this->translate($this->currentLocale, false)->setHeader($header);
    }

    /**
     * @return string
     */
    public function getSubHeader()
    {
        return $this->translate()->subHeader;
    }

    /**
     * @param string $subHeader
     */
    public function setSubHeader($subHeader)
    {
        $this->translate($this->currentLocale, false)->setSubHeader($subHeader);
    }

    /**
     * @return string
     */
    public function getPerex()
    {
        return $this->translate()->perex;
    }

    /**
     * @param string $perex
     */
    public function setPerex($perex)
    {
        $this->translate($this->currentLocale, false)->setPerex($perex);
    }


    /**
     * @return string
     */
    public function getContent()
    {
        return $this->translate()->content;
    }

    /**
     * @param string $content
     */
    public function setContent($content)
    {
        $this->translate($this->currentLocale, false)->setContent($content);
    }


    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->translate()->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->translate($this->currentLocale, false)->setDescription($description);
    }













    /**
     * @param PageEntity $page
     */
    public function addPage(PageEntity $page)
    {
        $this->pages->add($page);

    }





    /**
     * @param mixed $route
     *
     * @return $this
     */
    public function setRoute($route)
    {
        $this->route = $route;
        return $this;
    }

    /**
     * @return Devrun\CmsModule\Entities\RouteEntity
     */
    public function getRoute()
    {
        return $this->route;
    }

    /**
     * @return PackageEntity
     */
    public function getPackage(): PackageEntity
    {
        return $this->package;
    }

    /**
     * @param PackageEntity $package
     * @return ArticleEntity
     */
    public function setPackage(PackageEntity $package): ArticleEntity
    {
        $this->package = $package;
        return $this;
    }

    /**
     * @return PageEntity
     */
    public function getPage(): PageEntity
    {
        return $this->page;
    }

    /**
     * @param PageEntity $page
     * @return ArticleEntity
     */
    public function setPage(PageEntity $page): ArticleEntity
    {
        $this->page = $page;
        return $this;
    }









    public function __clone()
    {
        $this->id = NULL;
        $this->images = [];
        $this->translations = [];
        $this->createdBy = null;
        $this->updatedBy = null;
        $this->deletedBy = null;
        $this->inserted = null;
        $this->updated = null;
    }

}