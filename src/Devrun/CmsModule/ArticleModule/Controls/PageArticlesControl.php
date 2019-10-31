<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    PageArticlesControl.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\ArticleModule\Controls;

use Devrun\ArticleModule\Entities\ArticleEntity;
use Devrun\ArticleModule\Facades\ArticleFacade;
use Devrun\CmsModule\Controls\AdminControl;
use Devrun\CmsModule\Controls\DataGrid;
use Devrun\CmsModule\Entities\RouteEntity;
use Devrun\CmsModule\Presenters\PagePresenter;
use Nette\Utils\Html;

interface IPageArticlesControlFactory
{
    /** @return PageArticlesControl */
    function create();
}

class PageArticlesControl extends AdminControl
{

    /** @var RouteEntity */
    private $route;

    /** @var ArticleFacade @inject */
    public $articleFacade;


    public function render()
    {
        $template = $this->getTemplate();
        $link = $this->getPresenter()->link(':Cms:Article:Translate:update');

        $template->link = $link;
        $template->render();
    }

    protected function attached($presenter)
    {
        if ($presenter instanceof PagePresenter) {
            $this->route = $presenter->getRouteEntity();
        }

        parent::attached($presenter);
    }




    public function handleAddArticle()
    {
        if ($page = $this->page) {
            $entity = $this->articleFacade->createNewEntity();

            $entity->addPage($page);
            $this->articleFacade->getEntityManager()->persist($entity)->flush();
            $this->flashMessage('Article added', 'info');
        }

        $this->ajaxRedirect();
    }



    public function handleDelete($id)
    {
        if (!$entity = $this->articleFacade->getArticleRepository()->find($id)) {
            $this->flashMessage("Article $id not found", 'warning');

        } else {
            $this->articleFacade->getEntityManager()->remove($entity)->flush();
            $this->flashMessage("Article $id have been deleted", 'warning');
        }

        $this->ajaxRedirect();
    }


    public function handlePublic($id)
    {
        if (!$entity = $this->articleFacade->getArticleRepository()->find($id)) {
            $this->flashMessage("Article $id not found", 'warning');

        } else {
            $entity->public = !$entity->public;
            $this->articleFacade->getEntityManager()->persist($entity)->flush();

            $message = $entity->public
                ? "Article $id have been published"
                : "Article $id have been unpublished";

            $this->flashMessage($message, 'warning');
        }

        $this->ajaxRedirect();
    }


    public function handleTest($id)
    {
        dump($id);


        $template = $this->getTemplate();

        $fileName = __DIR__ . "/PageArticlesEditControl.latte";

        $template->setFile($fileName);



        $template->render();


        $this->getPresenter()->terminate();
    }

    public function handleRedraw($id)
    {
        // $this['grid']->reload();
        $this['grid']->redrawItem($id);
        $this->pageRedraw();
    }



    protected function createComponentGrid($name)
    {
        $grid = new DataGrid();
        $grid->setTranslator($this->translator);
        $grid->setItemsPerPageList([15, 20, 30, 50, 100]);


        $query = $this->articleFacade->getArticleRepository()->createQueryBuilder('e')
            ->addSelect('r')
            ->addSelect('id')
            ->join('e.route', 'r')
            ->join('e.identify', 'id')
            ->leftJoin('e.translations', 't')
            ->andWhere('r = :route')->setParameter('route', $this->route)
            ->andWhere('e.deletedBy IS NULL');



        $grid->setDataSource($query);


        $grid->addColumnText('identifier', 'Klíč', 'identify.identifier')
            ->setSortable()
            ->setFilterText()
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('id.identifier LIKE :likeIdentifier')->setParameter('likeIdentifier', "%$value%");
            });



        $grid->addColumnText('header', 'Hlavička')
            ->setRenderer(function (ArticleEntity $row) {
                $options = $row->getIdentify()->getOptions();
                $myOption = isset($options['header']) ? $options['header'] : [];
                $result = Html::el('p')
//                    ->setHtml($row->getHeader())
                    ->setAttribute('disabled', 'true');

                if ($myOption) {
                    if ($myOption['enable'] && $myOption['type'] == 'inline') {
                        $result = Html::el('p')
                            ->setHtml($row->getHeader())
                            ->setAttribute('contenteditable', 'true')
                            ->setAttribute('data-namespace', $row->getIdentifier())
                            ->setAttribute('data-source', 'header');
                    }
                }

                return $result;
            })
            ->setSortable()
            ->setFilterText()
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('t.header LIKE :like')->setParameter('like', "%$value%");
            });

        $grid->addColumnText('subHeader', "Sub hlavička", 'translation.subHeader')
            ->setRenderer(function (ArticleEntity $row) {
                $options = $row->getIdentify()->getOptions();
                $myOption = isset($options['subHeader']) ? $options['subHeader'] : [];
                $result = Html::el('p')
//                    ->setHtml($row->getHeader())
                    ->setAttribute('disabled', 'true');

                if ($myOption) {
                    if ($myOption['enable'] && $myOption['type'] == 'inline') {
                        $result = Html::el('p')
                            ->setHtml($row->getSubHeader())
                            ->setAttribute('contenteditable', 'true')
                            ->setAttribute('data-namespace', $row->getIdentifier())
                            ->setAttribute('data-source', 'subHeader');
                    }
                }

                return $result;
            })
            ->setSortable()
            ->setFilterText()
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('t.subHeader LIKE :like')->setParameter('like', "%$value%");
            });



        $grid->addColumnText('description', 'Popis')
            ->setSortable()
            ->setFilterText();



        $grid->addAction('edit', 'Edit')
            ->setIcon('edit fa-2x')
            ->setRenderer(function (ArticleEntity $article) {
                $html = Html::el("a")
                    ->addText(" Edit detail")
                    ->setAttribute('class', 'btn btn-xs btn-default')
                    ->setAttribute('data-modal-dialog', 'modal-info')
                    ->setAttribute('data-modal-success', $this->link('redraw!', ['id' => $article->getId()]))
                    ->setAttribute('data-modal-title', 'Úprava článku')
                    ->setAttribute('data-modal-type', 'modal-lg')
                    ->setAttribute('data-modal-autoclose', 'true')
                    ->href($this->presenter->link(":Cms:Article:Default:edit", ['id' => $article->getId()]));

                $html->insert(0, Html::el('span')->setAttribute('class', 'fa fa-edit fa-2x'));
                return $html;
            });


//        $grid->addAction('delete', 'Delete', 'delete!')
//            ->setIcon('trash fa-2x')
//            ->setClass('_ajax btn btn-xs btn-danger')
//            ->setConfirm(function ($item) {
//                return "Opravdu chcete smazat článek [id: {$item->id} {$item->header}]?";
//            });


        return $grid;
    }


    protected function createComponentForm()
    {
        $form = $this->articleFacade->getArticleFormFactory()->create();

        $form->bootstrap3Render()->create();

        return $form;
    }


}