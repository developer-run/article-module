<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    EnvironmentListener.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Listeners;

use Devrun\Application\UI\Presenter\BasePresenter;
use Devrun\ArticleModule\Entities\ArticleEntity;
use Devrun\ArticleModule\Facades\ArticlePipe;
use Devrun\ArticleModule\Repositories\ArticleRepository;
use Devrun\CmsModule\Repositories\RouteRepository;
use Kdyby\Events\Subscriber;
use Nette;

class ArticlePresenterListener implements Subscriber
{

    /** @var RouteRepository */
    private $routeRepository;

    /** @var ArticleRepository */
    private $articleRepository;

    /** @var ArticlePipe */
    private $articlePipe;

    /**
     * ArticlePresenterListener constructor.
     *
     * @param RouteRepository   $routeRepository
     * @param ArticleRepository $articleRepository
     * @param ArticlePipe       $articlePipe
     */
    public function __construct(RouteRepository $routeRepository, ArticleRepository $articleRepository, ArticlePipe $articlePipe)
    {
        $this->routeRepository   = $routeRepository;
        $this->articleRepository = $articleRepository;
        $this->articlePipe       = $articlePipe;
    }


    public function onBeforeRender(Nette\Application\UI\Presenter $presenter)
    {
        $sortArticles = [];

        if (!$route = $this->routeRepository->getRouteFromApplicationRequest($presenter->getRequest())) {
            $route = $this->routeRepository->findRouteFromApplicationPresenter($presenter);
        }

        if ($route) {
            /** @var ArticleEntity[] $articles */
            $articles = $this->articleRepository->createQueryBuilder('e')
                ->addSelect('t')
                ->addSelect('i')
                ->addSelect('id')
                ->leftJoin('e.translations', 't')
                ->leftJoin('e.image', 'i')
                ->leftJoin('e.identify', 'id')
                ->where('e.route = :route')->setParameter('route', $route)
                ->getQuery()
                ->getResult();

            foreach ($articles as $article) {
                $sortArticles[$article->getIdentify()->getIdentifier()] = $article;
            }
        }

        $this->articlePipe->setArticles($sortArticles);
    }




    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            BasePresenter::BEFORE_RENDER_EVENT,
        ];
    }

}