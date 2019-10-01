<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    ArticleIdentifyEntity.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Entities;

use Devrun\CmsModule\InvalidArgumentException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;
use Kdyby\Doctrine\Entities\MagicAccessors;

/**
 * Class ArticleIdentifyEntity
 *
 * @ORM\Entity
 * @ORM\Table(name="article_identify", indexes={
 *    @ORM\Index(name="article_identify_namespace_idx", columns={"namespace"}),
 *    @ORM\Index(name="article_identify_identifier_idx", columns={"identifier"}),
 * }, uniqueConstraints={@ORM\UniqueConstraint(
 *    name="namespace_name_idx", columns={"namespace", "name"}
 * )})
 *
 * @package Devrun\ArticleModule\Entities
 */
class ArticleIdentifyEntity
{

    use Identifier;
    use MagicAccessors;


    /**
     * @var ArticleEntity
     * @ORM\OneToMany(targetEntity="ArticleEntity", mappedBy="identify")
     */
    protected $articles;

    /**
     * @var string namespace
     * @ORM\Column(name="`namespace`", type="string")
     */
    protected $namespace;

    /**
     * @var string system name
     * @ORM\Column(type="string", nullable=true)
     */
    protected $name;

    /**
     * @var string identifier
     * @ORM\Column(type="string")
     */
    protected $identifier;

    /**
     * @var array
     * @ORM\Column(type="json_array")
     */
    protected $options = [];


    /**
     * ArticleIdentifyEntity constructor.
     */
    public function __construct($namespace)
    {
        $this->setIdentifier($namespace);
        $this->articles  = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @param string $namespace
     *
     * @return $this
     */
    public function setNamespace(string $namespace)
    {
        $this->namespace = $namespace;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     *
     * @return $this
     */
    public function setIdentifier(string $identifier)
    {
        if (!strpos($identifier, '.')) {
            throw new InvalidArgumentException('Identifier must have two words [namespace.name]');
        }
        $this->identifier = $identifier;

        $identify        = explode('.', $identifier);
        $name            = array_pop($identify);
        $namespace       = implode('.', $identify);
        $this->name      = $name;
        $this->namespace = $namespace;

        return $this;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }

    public function addArticle(ArticleEntity $articleEntity)
    {
        if (!$this->articles->contains($articleEntity)) {
            $this->articles->add($articleEntity);
        }
    }


}