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
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Repositories\RouteRepository;
use Devrun\Utils\Strings;
use Kdyby\Events\Subscriber;
use Kdyby\Translation\Translator;
use Nette\Application\Application;
use Nette\Application\Request;
use Nette\Application\UI\Presenter;
use Tracy\Debugger;

class ArticlePipe implements Subscriber
{

    /** @var ArticleRepository */
    private $articleRepository;

    /** @var RouteRepository */
    private $routeRepository;

    /** @var Translator */
    private $translator;

    /** @var Request */
    private $applicationRequest;

    /** @var RouteEntity */
    private $applicationRoute;

    /** @var ArticleEntity[] */
    private $articles = [];

    /** @var bool flush directly or onShutdown @see FlushListener */
    private $autoFlush = false;

    /**
     * ArticlePipe constructor.
     *
     * @param ArticleRepository $articleRepository
     * @param RouteRepository   $routeRepository
     * @param Translator        $translator
     * @param boolean           $autoFlush
     */
    public function __construct(bool $autoFlush, ArticleRepository $articleRepository, RouteRepository $routeRepository, Translator $translator)
    {
        $this->articleRepository = $articleRepository;
        $this->routeRepository   = $routeRepository;
        $this->translator        = $translator;
        $this->autoFlush         = $autoFlush;
    }


    public function getArticle($namespace, $source, array $params = array(), $applicationRoute = true, $createEmptyIfNotExist = true)
    {
        $modifyEntity = false;

        $entity = null;
        if (isset($this->articles[$namespace])) {
            $entity = $this->articles[$namespace];

        } else {
            /*
             * find namespace and name
             */
            $findBy = ['identify.identifier' => $namespace];

            if ($applicationRoute) {
                $findBy['route'] = $this->getApplicationRoute();
            }

            /** @var ArticleEntity $entity */
            if (!$entity = $this->articleRepository->findOneBy($findBy)) {
                if ($createEmptyIfNotExist) {
                    $entity = $this->createDemoArticle($namespace, $applicationRoute);
                    $entity->setPublic(true);

                    $modifyEntity = true;
                }
            }

            $this->articles[$namespace] = $entity;
        }

        if ($params) {
            $options = $entity->getIdentify()->getOptions();
            if (!isset($options[$source])) {
                $options[$source] = [
                    'enable' => true,
                    'type'   => isset($params['type']) ? $params['type'] : 'inline',
                    'editor' => isset($params['editor']) ? $params['editor'] : 'simple',
                ];

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


    private function createDemoArticle($namespace, $applicationRoute)
    {
        if (!$articleIdentifyEntity = $this->articleRepository->getEntityManager()->getRepository(ArticleIdentifyEntity::class)->findOneBy(['identifier' => $namespace])) {
            $articleIdentifyEntity = new ArticleIdentifyEntity($namespace);
        }

        $entity = new ArticleEntity($this->translator, $articleIdentifyEntity);

        if ($applicationRoute) {
            $entity->setRoute($this->getApplicationRoute());
        }

        /*
         * set header, subHeader, perex ...
         */

        return $entity;
    }


    /**
     * @return \Devrun\CmsModule\Entities\RouteEntity|null
     */
    private function getApplicationRoute()
    {
        if (null === $this->applicationRoute) {
            if (!$this->applicationRoute = $this->routeRepository->getRouteFromApplicationRequest($this->applicationRequest)) {
//                $this->applicationRoute = $this->routeRepository->findRouteFromApplicationPresenter($this->presenter);
            }
        }

        return $this->applicationRoute;
    }


    /**
     * @param ArticleEntity[] $articles
     */
    public function setArticles(array $articles)
    {
        $this->articles = $articles;
    }


    /**
     * @deprecated
     *
     * @param Application $application
     */
    public function onRequest(Application $application)
    {
        /** @var Presenter $presenter */
        if ($presenter = $application->getPresenter()) {
            $this->applicationRequest = $presenter->getRequest();

        } else {
            $this->applicationRequest = $application->getRequests()[0];
        }
    }


    public function onStartup(Presenter $presenter)
    {
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
//            'Nette\Application\Application::onRequest',
            'Nette\Application\UI\Presenter::onStartup'
        ];

    }
}