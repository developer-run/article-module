<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    ArticleTranslation.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Entities;

use Devrun\DoctrineModule\Entities\Attributes\Translation;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\MagicAccessors;

/**
 * Class ArticleTranslation
 *
 * @ORM\Entity
 * @ORM\Table(name="article_translation")
 */
class ArticleTranslationEntity
{
    const HEADER = "header";
    const SUB_HEADER = "subHeader";
    const PEREX = "perex";
    const CONTENT = "content";

    static private $fallback = [
        self::HEADER => "Lorem ipsum",
        self::SUB_HEADER => "Co je to \"Lorem ipsum\"?",
        self::PEREX => "Lorem ipsum (zkráceně lipsum) je označení pro standardní pseudolatinský text užívaný v grafickém designu a navrhování jako demonstrativní výplňový text při vytváření pracovních ukázek grafických návrhů (např. internetových stránek, rozvržení časopisů či všech druhů reklamních materiálů). Lipsum tak pracovně znázorňuje text v ukázkových maketách (tzv. mock-up) předtím, než bude do hotového návrhu vložen smysluplný obsah.",
        self::CONTENT => "Lorem ipsum (zkráceně lipsum) je označení pro standardní pseudolatinský text užívaný v grafickém designu a navrhování jako demonstrativní výplňový text při vytváření pracovních ukázek grafických návrhů (např. internetových stránek, rozvržení časopisů či všech druhů reklamních materiálů). Lipsum tak pracovně znázorňuje text v ukázkových maketách (tzv. mock-up) předtím, než bude do hotového návrhu vložen smysluplný obsah.
Pokud by se pro stejný účel použil smysluplný text, bylo by těžké hodnotit pouze vzhled, aniž by se pozorovatel nechal svést ke čtení obsahu. Pokud by byl naopak použit nesmyslný, ale pravidelný text (např. opakování „asdf asdf asdf…“), oko by při posuzování vzhledu bylo vyrušováno pravidelnou strukturou textu, která se od běžného textu liší. Text lorem ipsum na první pohled připomíná běžný text, slova jsou různě dlouhá, frekvence písmen je podobná běžné řeči, interpunkce vypadá přirozeně atd.",
    ];



    use MagicAccessors;
    use Translation;


    /**
     * @ORM\Column(type="text", length=65536, nullable=true)
     * @var string
     */
    protected $header;

    /**
     * @var string
     * @ORM\Column(type="text", length=65536, nullable=true)
     */
    protected $subHeader;

    /**
     * @var string
     * @ORM\Column(type="text", length=65536, nullable=true)
     */
    protected $perex;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $content;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    protected $description;

    /**
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * @param string $header
     */
    public function setHeader($header)
    {
        $this->header = html_entity_decode($header);
    }

    /**
     * @param string $subHeader
     */
    public function setSubHeader(string $subHeader)
    {
        $this->subHeader = html_entity_decode($subHeader);
    }

    /**
     * @param string $perex
     */
    public function setPerex(string $perex)
    {
        $this->perex = html_entity_decode($perex);
    }

    /**
     * @param string $content
     */
    public function setContent(string $content)
    {
        $this->content = html_entity_decode($content);
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = html_entity_decode($description);
    }

    /**
     * @return string
     */
    static public function getFallback($column)
    {
        return isset(self::$fallback[$column]) ? self::$fallback[$column] : null;
    }



    public function __clone()
    {
        $this->id = NULL;
        $this->translatable = null;
    }



}