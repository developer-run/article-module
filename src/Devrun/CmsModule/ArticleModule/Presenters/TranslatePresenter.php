<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    TranslatePresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\ArticleModule\Presenters;

use Devrun\ArticleModule\Entities\ArticleEntity;
use Devrun\ArticleModule\Facades\ArticleFacade;
use Devrun\CmsModule\Presenters\AdminPresenter;

class TranslatePresenter extends AdminPresenter
{

    /** @var ArticleFacade @inject */
    public $articleFacade;



    public function renderUpdate($namespace, $source, $route, $content)
    {
        /** @var ArticleEntity $entity */
        if ($entity = $this->articleFacade->getArticleRepository()->findOneBy(['identify.identifier' => $namespace, 'route' => $route])) {

            $entity->$source = html_entity_decode($content);
            $this->articleFacade->getEntityManager()->persist($entity);
            $entity->mergeNewTranslations();
            $this->articleFacade->getEntityManager()->flush();

            $this->payload->translate = true;
        }

        $this->sendPayload();
    }


}