<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2016
 *
 * @file    ArticleFacade.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Facades;

use Devrun\ArticleModule\Entities\ArticleEntity;
use Devrun\ArticleModule\Repositories\ArticleRepository;
use Devrun\CmsModule\ArticleModule\Forms\IArticleFormFactory;
use Kdyby\Doctrine\EntityManager;
use Nette\Object;

class ArticleFacade extends Object
{

    /** @var ArticleRepository */
    private $articleRepository;

    /** @var IArticleFormFactory */
    private $articleFormFactory;

    /** @var EntityManager */
    private $em;

    /**
     * ArticleFacade constructor.
     *
     * @param ArticleRepository $articleRepository
     */
    public function __construct(ArticleRepository $articleRepository, IArticleFormFactory $articleFormFactory)
    {
        $this->articleRepository  = $articleRepository;
        $this->articleFormFactory = $articleFormFactory;
        $this->em                 = $this->articleRepository->getEntityManager();
    }

    /**
     * @return ArticleRepository
     */
    public function getArticleRepository()
    {
        return $this->articleRepository;
    }

    /**
     * @return IArticleFormFactory
     */
    public function getArticleFormFactory()
    {
        return $this->articleFormFactory;
    }


    /**
     * @return EntityManager
     */
    public function getEntityManager()
    {
        return $this->em;
    }

    /**
     * @return ArticleEntity
     */
    public function createNewEntity()
    {
        $entity = new ArticleEntity();
        $entity->setName('new article');
        return $entity;
    }


}