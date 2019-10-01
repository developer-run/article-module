<?php
/**
 * This file is part of ArticleModule.
 * Copyright (c) 2017
 *
 * @file    ArticleQuery.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Repositories\Queries;

use Devrun\ArticleModule\Entities\ArticleEntity;
use Kdyby;
use Kdyby\Doctrine\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;

class ArticleQuery extends QueryObject
{
    /**
     * @var array|\Closure[]
     */
    private $filter = [];

    /**
     * @var array|\Closure[]
     */
    private $select = [];



    /**
     * @param $route
     *
     * @return $this
     */
    public function byRoute($route)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($route) {
            $qb->andWhere('q.route = :route')->setParameter('route', $route);
        };
        return $this;
    }


    public function byPackage($package)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($package) {
            $qb->andWhere('r.package = :package')->setParameter('package', $package);
        };
        return $this;
    }



    public function withTranslations()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('t')
                ->join('q.translations', 't');
        };
        return $this;
    }

    public function withRoute()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('r')
                ->join('q.route', 'r');
        };
        return $this;
    }

    public function withImage()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('img')
                ->join('q.image', 'img');
        };
        return $this;
    }

    public function withImages()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('imgs')
                ->join('q.images', 'imgs');
        };
        return $this;
    }




    /**
     * @param Queryable $repository
     *
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder|QueryBuilder
     */
    protected function doCreateQuery(Queryable $repository)
    {
        $qb = $this->createBasicDql($repository);

        foreach ($this->select as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

    protected function doCreateCountQuery(Kdyby\Persistence\Queryable $repository)
    {
        return $this->createBasicDql($repository)->select('COUNT(q.id)');
    }


    /**
     * @param Queryable $repository
     *
     * @return QueryBuilder
     */
    private function createBasicDql(Queryable $repository)
    {
        $qb = $repository->createQueryBuilder()
            ->select('q')->from(ArticleEntity::getClassName(), 'q');

        foreach ($this->filter as $modifier) {
            $modifier($qb);
        }

        return $qb;
    }

}