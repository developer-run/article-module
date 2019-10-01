<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    RouteListener.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Listeners;


use Devrun\ArticleModule\Entities\ArticleEntity;
use Devrun\ArticleModule\Facades\ArticleFacade;
use Devrun\CmsModule\Entities\PackageEntity;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Facades\PackageFacade;
use Kdyby\Events\Subscriber;

class RouteListener implements Subscriber
{

    /** @var ArticleFacade */
    private $articleFacade;



    /**
     * RouteListener constructor.
     *
     * @param ArticleFacade $articleFacade
     */
    public function __construct(ArticleFacade $articleFacade)
    {
        $this->articleFacade = $articleFacade;
    }


    public function onCopyRoute(RouteEntity $newRouteEntity, RouteEntity $oldRouteEntity, PackageEntity $newPackage, PackageEntity $oldPackage = null)
    {
        $em = $this->articleFacade->getEntityManager();
        $articleRepository = $this->articleFacade->getArticleRepository();

        $query = $articleRepository->getQuery()
            ->withRoute()
//            ->withImage()
//            ->withImages()
            ->withTranslations()
            ->byPackage($oldPackage)
            ->byRoute($oldRouteEntity);

        /** @var ArticleEntity[] $oldArticleEntities */
        if (!empty($oldArticleEntities = $articleRepository->fetch($query)->getIterator())) {

            foreach ($oldArticleEntities as $oldArticleEntity) {

                $oldArticleTranslations  = $oldArticleEntity->getTranslations();
                $newArticleEntity = clone $oldArticleEntity;
                $newArticleEntity->setRoute($newRouteEntity);
                $newArticleEntity->setInserted(null);
                $newArticleEntity->setUpdated(null);

                foreach ($oldArticleTranslations as $oldArticleTranslation) {
                    $newArticleTranslationEntity = clone $oldArticleTranslation;
                    $newArticleEntity->addTranslation($newArticleTranslationEntity);
                }

                $em->persist($newArticleEntity);
            }

            return true;
        }

        return false;
    }



    /**
     * Returns an array of events this subscriber wants to listen to.
     *
     * @return array
     */
    public function getSubscribedEvents()
    {
        return [
            PackageFacade::EVENT_COPY_ROUTE
        ];
    }
}