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
use Nette;

class TranslatePresenter extends AdminPresenter
{

    /** @var ArticleFacade @inject */
    public $articleFacade;


    /**
     * signal to update article
     *
     * @param $namespace
     * @param $source
     * @param $routeId
     * @param $content
     * @throws Nette\Application\AbortException
     * @throws Nette\Utils\AssertionException
     */
    public function renderUpdate($namespace, $source, $type, $pageId, $packageId, $routeId, array $params = [], string $content = '')
    {
        Nette\Utils\Validators::assert($namespace, 'string', __METHOD__ . " `namespace` ");
        Nette\Utils\Validators::assert($source, 'string', __METHOD__ . " `source` ");
        Nette\Utils\Validators::assert($type, 'string', __METHOD__ . " `type` ");

        /*
         * set where find article
         */
        $findBy = ['identify.identifier' => $namespace];

        if ($params['page'] ?? false) {
            $findBy['page'] = $pageId;

        } elseif ($params['package'] ?? false) {
            $findBy['package'] = $packageId;

        } elseif ($params['route'] ?? true) {
            $findBy['route'] = $routeId;
        }

        /** @var ArticleEntity $entity */
        if ($entity = $this->articleFacade->getArticleRepository()->findOneBy($findBy)) {

            $parseContent = $type == 'inline' ? html_entity_decode($this->filterInline($content)) : html_entity_decode($content);
            $entity->$source = $parseContent;
            $this->articleFacade->getEntityManager()->persist($entity);
            $entity->mergeNewTranslations();
            $this->articleFacade->getEntityManager()->flush();

            $this->payload->translate = true;

        } else {
            $this->payload->translate = false;
        }

        $this->sendPayload();
    }

    /**
     * remove p attribute
     * <p>text</p> -> text
     * <h3>text</h3> -> text
     *
     * @param string $content
     * @return string|string[]|null
     */
    private function filterInline(string $content)
    {
        return preg_replace('/<(p|h[1-6])\b[^>]*>(.*)<\/(p|h[1-6])>/i', '$2', $content);
    }


}