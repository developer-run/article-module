<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    ArticleRepository.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\Repositories;

use Devrun\ArticleModule\Entities\ArticleEntity;
use Devrun\ArticleModule\Repositories\Queries\ArticleQuery;
use Kdyby\Doctrine\EntityRepository;

class ArticleRepository extends EntityRepository
{
    const CATEGORY_UNKNOWN = 'unknown';
    const CATEGORY_ALL = 'all';



    public function getQuery()
    {
        return new ArticleQuery();
    }



}