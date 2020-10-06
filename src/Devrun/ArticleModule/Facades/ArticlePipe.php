<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    ArticlePipe.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Facades;

use Devrun\ArticleModule\Entities\ArticleEntity;
use Devrun\ArticleModule\Entities\ArticleIdentifyEntity;
use Devrun\ArticleModule\Entities\ArticleTranslationEntity;
use Devrun\ArticleModule\Repositories\ArticleRepository;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Entities\PageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\NotFoundResourceException;
use Devrun\CmsModule\Presenters\TCmsPresenter;
use Kdyby\Doctrine\EntityManager;
use Kdyby\Events\Subscriber;
use Kdyby\Translation\Translator;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;

class ArticlePipe implements Subscriber
{

    /** @var ArticleRepository */
    private $articleRepository;

    /** @var EntityManager */
    private $entityManager;

    /** @var Translator */
    private $translator;

    /** @var Request */
    private $applicationRequest;

    /** @var RouteEntity */
    private $routeEntity;

    /** @var PageEntity */
    private $pageEntity;

    /** @var PackageEntity */
    private $packageEntity;

    /** @var ArticleEntity[] */
    private $articles = [];

    /** @var bool flush directly or onShutdown @see FlushListener */
    private $autoFlush = false;

    /**
     * ArticlePipe constructor.
     *
     * @param boolean $autoFlush
     * @param ArticleRepository $articleRepository
     * @param Translator $translator
     * @param EntityManager $entityManager
     */
    public function __construct(bool $autoFlush, ArticleRepository $articleRepository, Translator $translator, EntityManager $entityManager)
    {
        $this->entityManager     = $entityManager;
        $this->articleRepository = $articleRepository;
        $this->translator        = $translator;
        $this->autoFlush         = $autoFlush;
    }


    public function getArticle($namespace, $source, array $params = array())
    {
        $modifyEntity = false;
        $readyState   = true;

        $entity = null;
        if (isset($this->articles[$namespace])) {
            $entity = $this->articles[$namespace];

        } else {

            try {
                /*
                 * set where find article
                 */
                $findBy = ['identify.identifier' => $namespace];

                if ($params['page'] ?? false) {
                    $findBy['page'] = $this->getPageEntity();

                } elseif ($params['package'] ?? false) {
                    $findBy['package'] = $this->getPackageEntity();

                } elseif ($params['route'] ?? true) {
                    $findBy['route'] = $this->getRouteEntity();
                }

                /** @var ArticleEntity $entity */
                if (!$entity = $this->articleRepository->findOneBy($findBy)) {
                    $entity = $this->createEmptyArticle($namespace, $findBy);
                    $modifyEntity = true;
                }

                $this->articles[$namespace] = $entity;

            } catch (\Devrun\CmsModule\NotFoundResourceException $exception) {
                $readyState = false;
                $this->articles[$namespace] = $entity;
                $entity = $this->createEmptyArticle($namespace);
                $entity->$source = $exception->getMessage();
            }
        }

        if ($readyState && $params) {

            $options = $entity->getIdentify()->getOptions();
            if (!isset($options[$source]) /* || $options != $params */) {
                $options[$source] = $params;
                $entity->getIdentify()->setOptions($options);
                $this->articles[$namespace] = $entity;

                $modifyEntity = true;
            }

            if (!$entity->$source) {
                $fallTranslate = "article.$namespace.$source";
                $translate     = $this->translator->domain('article')->translate($namespace . '.' . $source);

                if ($translate != $fallTranslate) {
                    if (isset($entity->$source)) {
                        $entity->$source = $translate;
                    }

                } else {
                    // fallback [lorem ipsum]
                    if (isset($entity->$source)) {
                        $entity->$source = ArticleTranslationEntity::getFallback($source);
                    }
                }

                $this->articles[$namespace] = $entity;

                $modifyEntity = true;
            }
        }

        if ($modifyEntity) {
            $entity->mergeNewTranslations();

            /*
             * @todo experiment flush se provede po onShutdown
             * @see FlushListener
             */
            $this->articleRepository->getEntityManager()->persist($entity);

            if (!$this->autoFlush) {
                $this->articleRepository->getEntityManager()->flush();
            }
        }

        return $entity;
    }


    /**
     * @param string $namespace
     * @param array $params  [route => ..., page => ..., package => ...]
     * @return ArticleEntity
     */
    private function createEmptyArticle(string $namespace, array $params = [])
    {
        if (!$articleIdentifyEntity = $this->articleRepository->getEntityManager()->getRepository(ArticleIdentifyEntity::class)->findOneBy(['identifier' => $namespace])) {
            $articleIdentifyEntity = new ArticleIdentifyEntity($namespace);
        }

        $entity = (new ArticleEntity($this->translator, $articleIdentifyEntity));

        if ($params['route'] ?? false) $entity->setRoute($this->getRouteEntity());
        if ($params['page'] ?? false) $entity->setPage($this->getPageEntity());
        if ($params['package'] ?? false) $entity->setPackage($this->getPackageEntity());

        /*
         * set header, subHeader, perex ...
         */
        $entity->setPublic(true);
        return $entity;
    }


    /**
     * @return \Devrun\CmsModule\Entities\RouteEntity
     */
    private function getRouteEntity(): RouteEntity
    {
        if (!$this->routeEntity) {
            if ($route = $this->applicationRequest->getParameter('route')) {
                $this->routeEntity = $this->entityManager->getRepository(RouteEntity::class)->find($route);
            }
        }

        if (!$this->routeEntity) {
            throw new NotFoundResourceException("Unknown route for article");
        }
        return $this->routeEntity;
    }

    /**
     * @return PageEntity
     */
    protected function getPageEntity(): PageEntity
    {
        if (!$this->pageEntity) {
            if ($page = $this->applicationRequest->getParameter('page')) {
                $this->pageEntity = $this->entityManager->getRepository(PageEntity::class)->find($page);
            }
        }

        if (!$this->pageEntity) {
            throw new NotFoundResourceException("Unknown page for article");
        }
        return $this->pageEntity;
    }

    /**
     * @return PackageEntity
     */
    protected function getPackageEntity(): PackageEntity
    {
        if (!$this->packageEntity) {
            if ($package = $this->applicationRequest->getParameter('package')) {
                $this->packageEntity = $this->entityManager->getRepository(PackageEntity::class)->find($package);
            }
        }

        if (!$this->packageEntity) {
            throw new NotFoundResourceException("Unknown package for article");
        }
        return $this->packageEntity;
    }





    /**
     * @param ArticleEntity[] $articles
     */
    public function setArticles(array $articles)
    {
        $this->articles = $articles;
    }


    /**
     * @param Presenter|TCmsPresenter $presenter
     * @throws \ReflectionException
     */
    public function onStartup(Presenter $presenter)
    {
        if (isset(class_uses_recursive($presenter)[TCmsPresenter::class])) {

            try {
                $this->pageEntity    = $presenter->getPageEntity();
                $this->routeEntity   = $presenter->getRouteEntity();
                $this->packageEntity = $presenter->getPackageEntity();

            } catch (\Devrun\CmsModule\NotFoundResourceException $exception) {

            }

        }

        $this->applicationRequest = $presenter->getRequest();
    }


    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            'Nette\Application\UI\Presenter::onStartup'
        ];

    }
}