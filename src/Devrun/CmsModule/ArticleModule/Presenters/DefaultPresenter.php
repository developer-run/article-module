<?php
/**
 * This file is part of the devrun2016
 * Copyright (c) 2016
 *
 * @file    DefaultPresenter.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\ArticleModule\Presenters;

use Devrun\ArticleModule\Entities\ArticleEntity;
use Devrun\ArticleModule\Facades\ArticleFacade;
use Devrun\CmsModule\ArticleModule\Forms\ArticleForm;
use Devrun\CmsModule\ArticleModule\Forms\ArticleOptionsForm;
use Devrun\CmsModule\ArticleModule\Forms\IArticleFormFactory;
use Devrun\CmsModule\Controls\FlashMessageControl;
use Devrun\CmsModule\Facades\PageFacade;
use Devrun\CmsModule\Facades\TranslateFacade;
use Devrun\CmsModule\Presenters\AdminPresenter;
use Devrun\CmsModule\TranslateException;
use Nette\Forms\Container;
use Nette\Utils\ArrayHash;
use Nette\Utils\Html;
use Tracy\Debugger;

class DefaultPresenter extends AdminPresenter
{

    /** @var ArticleFacade @inject */
    public $articleFacade;

    /** @var IArticleFormFactory @inject */
    public $articleForm;

    /** @var TranslateFacade @inject */
    public $translateFacade;


    /** @var ArticleEntity */
    private $articleEntity;




    public function handleRedrawGrid($id)
    {
        $this['grid']->redrawItem($id);
    }

    public function handleDelete($id)
    {
        /** @var ArticleEntity $articleEntity */
        if ($articleEntity = $this->articleFacade->getArticleRepository()->find($id)) {
            $this->userFacade->getUserRepository()->getEntityManager()->remove($articleEntity)->flush();
            $this->flashMessage('Článek smazán', 'success');
        }

        $this->ajaxRedirect();
    }


    public function handleSoftDelete($id)
    {
        /** @var ArticleEntity $articleEntity */
        if ($articleEntity = $this->articleFacade->getArticleRepository()->find($id)) {
            $articleEntity->setDeletedBy($this->getUserEntity());
            $this->userFacade->getUserRepository()->getEntityManager()->persist($articleEntity)->flush();
            $this->flashMessage('Článek smazán', 'success');
        }

        $this->ajaxRedirect();
    }


    public function resetArticles($ids)
    {
        $flush = false;
        foreach ($ids as $id) {
            if ($this->resetArticle($id)) {
                $flush = true;
            }
        }

        if ($flush) {
            $this->userFacade->getUserRepository()->getEntityManager()->flush();
            $this->flashMessage('Články resetovány', FlashMessageControl::TOAST_TYPE, "Articles reset", FlashMessageControl::TOAST_SUCCESS);
        }

        if ($this->isAjax()) {
            $this['grid']->reload();
        }

        $this->ajaxRedirect('this', null, ['flash']);
    }

    public function resetArticle($id)
    {
        /** @var ArticleEntity $articleEntity */
        if ($articleEntity = $this->articleFacade->getArticleRepository()->find($id)) {

            $modify = false;
            foreach ($articleEntity->getIdentify()->getOptions() as $key => $option) {
                if (isset($option['enable']) && $option['enable']) {

                    $domain = 'article';
                    if (isset($articleEntity->$key)) {
                        $articleEntity->$key = $this->translator->domain($domain)->translate($articleEntity->getIdentifier() . ".$key");
                        $articleEntity->mergeNewTranslations();

                        $modify = true;
                    }
                }
            }

            if ($modify) {
                $this->userFacade->getUserRepository()->getEntityManager()->persist($articleEntity);
                return true;
            }
        }

        return false;
    }



    public function handleReset($id)
    {
        if ($this->resetArticle($id)) {
            $this->userFacade->getUserRepository()->getEntityManager()->flush();
            $this->flashMessage('Články resetován', FlashMessageControl::TOAST_TYPE, "Articles reset", FlashMessageControl::TOAST_SUCCESS);
            $this->flashMessage('Články resetován');

        } else {
            $this->flashMessage('Článek se nepodařilo resetovat', FlashMessageControl::TOAST_TYPE, "Articles reset", FlashMessageControl::TOAST_WARNING);
        }

        if ($this->isAjax()) {
            $this['grid']->reload();
        }

        $this->ajaxRedirect('this', null, ['flash']);
//        $this->ajaxRedirect();
    }


    public function actionEdit($id)
    {
        if (!$this->articleEntity = $this->articleFacade->getArticleRepository()->find($id)) {
            $this->flashMessage("Článek nenalezen", 'warning');
            $this->redirect('default');
        }

        $this->template->article = $this->articleEntity;
    }


    public function actionEditOptions($id)
    {
        if (!$this->articleEntity = $this->articleFacade->getArticleRepository()->find($id)) {
            $this->flashMessage("Článek nenalezen", 'warning');
            $this->redirect('default');
        }


        $this->template->article = $this->articleEntity;
    }


    protected function createComponentGrid($name)
    {
        $grid = $this->createGrid($name);
        $grid->setTranslator($this->translator);
        $grid->setRefreshUrl(false)->setRememberState(false);

        $query = $this->articleFacade->getArticleRepository()->createQueryBuilder('e')
            ->addSelect('r')
            ->addSelect('pa')
            ->leftJoin("e.route", 'r')
            ->leftJoin("r.package", 'pa')
            ->andWhere('e.deletedBy IS NULL');

        if (!$this->getUser()->isAllowed('Cms:Article:Default', 'editAllArticles')) {
            $query->andWhere('pa.user = :user')->setParameter('user', $this->user->getId());
        }

        /** @var ArticleEntity[] $articles */
        $articles = $query->getQuery()->getResult();

        $modules = [];
        $packages = [];
        foreach ($articles as $article) {
            if ($article->getRoute()) {
                $modules[$article->getRoute()->getPackage()->getModule()] = ucfirst($article->getRoute()->getPackage()->getModule());
                $packages[$article->getRoute()->getPackage()->getName()] = ucfirst($article->getRoute()->getPackage()->getName());
            }
        }


        $query = $this->articleFacade->getArticleRepository()->createQueryBuilder('e')
            ->addSelect('r')
            ->addSelect('id')
            ->addSelect('p')
            ->addSelect('t')
            ->addSelect('pa')
            ->leftJoin("e.translations", 't')
            ->leftJoin("e.route", 'r')
            ->leftJoin("e.identify", 'id')
            ->leftJoin("r.package", 'pa')
            ->leftJoin("r.page", 'p')
            ->andWhere('e.deletedBy IS NULL');

        if (!$this->getUser()->isAllowed('Cms:Article:Default', 'editAllArticles')) {
            $query->andWhere('pa.user = :user')->setParameter('user', $this->user->getId());
        }




        $grid->setDataSource($query);

        $grid->addColumnText('identifiers', 'Klíč', 'id.identifier')
            ->setRenderer(function (ArticleEntity $row) {
                if ($row->getRoute()) {
                    $result = Html::el('a')->href($this->link(":Cms:Page:edit", ['id' => $row->getRoute()->page->id]))
                        ->setHtml($row->getIdentify()->getIdentifier());
                } else {
                    $result = $row->getNamespace();
                }

                return $result;
            })
            ->setSortable()
            ->setFilterText()
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('id.identifier LIKE :identifier')->setParameter('identifier', "%$value%");
            });

        $grid->addColumnText('header', 'Header', 't.header')
            ->setRenderer(function (ArticleEntity $row) {
                $result = Html::el('p')
                    ->setHtml($row->getHeader())
//                    ->setAttribute('contenteditable', 'true')
                    ->setAttribute('data-namespace', $row->getNamespace())
                    ->setAttribute('data-source', 'header');
                if ($route = $row->getRoute()) {
                    $result->setAttribute('data-route', $row->getRoute()->id);
                }
                return $result;
            })
            ->setSortable()
            ->setFilterText()
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('t.header LIKE :header')->setParameter('header', "%$value%");
            });

        $grid->addColumnText('subHeader', 'Sub header', 't.subHeader')
            ->setRenderer(function (ArticleEntity $row) {
                $result = Html::el('p')
                    ->setHtml($row->getSubHeader())
//                    ->setAttribute('contenteditable', 'true')
                    ->setAttribute('data-namespace', $row->getNamespace())
                    ->setAttribute('data-source', 'subHeader');
                if ($route = $row->getRoute()) {
                    $result->setAttribute('data-route', $row->getRoute()->id);
                }
                return $result;
            })
            ->setSortable()
            ->setFilterText()
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('t.subHeader LIKE :subHeader')->setParameter('subHeader', "%$value%");
            });

        $moduleList = array(null => 'Všechny') + $modules;

        $grid->addColumnText('module', 'Module', 'route.page.module')
            ->setSortable()
            ->setFilterSelect($moduleList)
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('p.module = :module')->setParameter('module', $value);
            });

        $packageList = array(null => 'Všechny') + $packages;

        $grid->addColumnText('package', 'Balíček', 'route.package.name')
            ->setSortable()
            ->setFilterSelect($packageList)
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('pa.name = :name')->setParameter('name', $value);
            });

        $grid->addColumnText('presenter', 'Page', 'route.page.presenter')
            ->setSortable()
            ->setFilterText()
            ->setCondition(function (\Kdyby\Doctrine\QueryBuilder $queryBuilder, $value) {
                $queryBuilder->andWhere('p.presenter LIKE :presenter')->setParameter('presenter', "%$value%");
            });





        if ($this->getUser()->isAllowed('Cms:Article:Default', 'edit')) {

            $grid->addAction('edit', 'Edit', 'edit')
                ->setRenderer(function (ArticleEntity $article) {
                    $html = Html::el("a")
                        ->addText(" Edit detail")
                        ->setAttribute('class', 'btn btn-xs btn-default')
                        ->setAttribute('data-modal-dialog', 'modal-info')
                        ->setAttribute('data-modal-success', $this->link('redrawGrid!', ['id' => $article->getId()]))
                        ->setAttribute('data-modal-title', 'Úprava článku')
                        ->setAttribute('data-modal-type', 'modal-lg')
                        ->setAttribute('data-modal-autoclose', 'true')
                        ->href($this->presenter->link(":Cms:Article:Default:edit", ['id' => $article->getId()]));

                    $html->insert(0, Html::el('span')->setAttribute('class', 'fa fa-edit fa-2x'));
                    return $html;
                });

//                ->setIcon('edit fa-2x')
//                ->setClass('btn btn-xs btn-default')
//                ->setDataAttribute('popup-dialog', 'modal-info')
//                ->setDataAttribute('popup-title', 'Úprava článku')
////                ->setDataAttribute('modal-success', $this->link('redrawGrid!'))
//                ->setDataAttribute('modal-success', $this->link('redraw!', ['id' => $article->getId()]))
//                ->setDataAttribute('auto-close', true)
//                ->setDataAttribute('popup-type', 'modal-lg');

        }

        if ($this->getUser()->isAllowed('Cms:Article:Default', 'editAllArticleAttributes')) {
            $grid->addInlineEdit()
                ->onControlAdd[] = function(Container $container) {
                $container->addText('header');
                $container->addText('subHeader');
            };

            $grid->getInlineEdit()->onSetDefaults[] = function(Container $container, $item) {
                $container->setDefaults([
                    'header' =>  $item->header,
                    'subHeader' => $item->subHeader,
                ]);
            };

            $grid->getInlineEdit()->onSubmit[] = function($id, $values) {
                /**
                 * Save new values
                 */
                if ($entity = $this->articleFacade->getArticleRepository()->find($id)) {

                    foreach ($values as $key => $value) {
                        if (isset($entity->$key)) {
                            $entity->$key = $value;
                        }
                    }

                    $this->articleFacade->getEntityManager()->persist($entity)->flush();
                }

            };



            $grid->addAction('editOptions', 'Options')
                ->setIcon('edit fa-2x')
                ->setClass('btn btn-xs btn-primary')
                ->setDataAttribute('modal-dialog', 'popup')
                ->setDataAttribute('modal-title', 'Úprava článku')
                ->setDataAttribute('auto-close', true)
                ->setDataAttribute('modal-type', 'modal-lg');


            $grid->addAction('softDelete', 'Soft delete', 'softDelete!')
                ->setIcon('trash fa-2x')
                ->setClass('_ajax btn btn-xs btn-warning')
                ->setConfirm(function ($item) {
                    return "Opravdu chcete smazat článek [id: {$item->id} {$item->namespace}]?";
                });

            $grid->addAction('delete', 'Delete', 'delete!')
                ->setIcon('trash fa-2x')
                ->setClass('_ajax btn btn-xs btn-danger')
                ->setConfirm(function ($item) {
                    return "Opravdu chcete smazat článek [id: {$item->id} {$item->namespace}]?";
                });

        }

        if ($this->getUser()->isAllowed('Cms:Article:Default', 'resetArticles')) {
            $grid->addAction('reset', 'Reset', 'reset!')
                ->setIcon('superpowers fa-2x')
                ->setClass('ajax btn btn-xs btn-warning')
                ->setConfirm(function ($item) {
                    return "Opravdu chcete resetovat článek [id: {$item->id} {$item->namespace}]?";
                });


            $grid->addGroupAction('Reset articles')->onSelect[] = [$this, 'resetArticles'];
        }



        return $grid;
    }


    /**
     * @return ArticleForm
     */
    protected function createComponentArticleForm()
    {
        $domain = 'article';
        $form = $this->articleForm->create();
        $form
            ->setOptions($this->articleEntity->getIdentify()->getOptions())
            ->setEditReference($this->getUser()->isAllowed('Cms:Article:Default', 'editAllArticleAttributes'))
            ->create()
            ->bootstrap3Render()
            ->bindEntity($this->articleEntity)
            ->setDefaults([
                'header'         => $this->articleEntity->getHeader(),
                'subHeader'      => $this->articleEntity->getSubHeader(),
                'perex'          => $this->articleEntity->getPerex(),
                'content'        => $this->articleEntity->getContent(),
                'description'    => $this->articleEntity->getDescription(),
                'refHeader'      => $this->translator->domain($domain)->translate($this->articleEntity->getIdentifier() . '.header'),
                'refSubHeader'   => $this->translator->domain($domain)->translate($this->articleEntity->getIdentifier() . '.subHeader'),
                'refPerex'       => $this->translator->domain($domain)->translate($this->articleEntity->getIdentifier() . '.perex'),
                'refContent'     => $this->translator->domain($domain)->translate($this->articleEntity->getIdentifier() . '.content'),
                'refDescription' => $this->translator->domain($domain)->translate($this->articleEntity->getIdentifier() . '.description'),
            ])
            ->onSuccess[] = function (ArticleForm $form, $values) use ($domain) {

            /** @var ArticleEntity $entity */
            $entity = $form->getEntity();
            $form->getEntityMapper()->getEntityManager()->persist($entity);
            $entity->mergeNewTranslations();

            try {
                $items  = [
                    'header'      => 'refHeader',
                    'subHeader'   => 'refSubHeader',
                    'perex'       => 'refPerex',
                    'content'     => 'refContent',
                    'description' => 'refDescription',
                ];
                foreach ($items as $key => $item) {
                    if (isset($values->$item)) {

                        $content     = $values->$item;
                        $translateId = $entity->getIdentifier() . '.' . $key;

                        $this->translateFacade->updateTranslate($domain, $translateId, $content);
                    }
                }

            } catch (TranslateException $e) {
                $this->flashMessage($e->getMessage(), FlashMessageControl::TOAST_TYPE, "Translate error", FlashMessageControl::TOAST_WARNING);
                $this->ajaxRedirect('this', null, ['flash']);
                return;
            }

            $form->getEntityMapper()->getEntityManager()->flush();


            $message = "Článek {$entity->getNamespace()} uložen";
            $this->flashMessage($message, FlashMessageControl::TOAST_TYPE, "Úprava článku", FlashMessageControl::TOAST_SUCCESS);

//            $this->restoreRequest($this->backlink);
            $this->ajaxRedirect('default', null, ['flash']);

        };

        return $form;
    }


    /**
     * @param $name
     *
     * @return ArticleOptionsForm
     */
    protected function createComponentArticleOptionsForm($name)
    {
        $form = new ArticleOptionsForm($this, $name);

        $options = $this->articleEntity->getIdentify()->getOptions();

        $form
            ->setOptions([
                'header' => [],
                'subHeader' => [],
                'perex' => [],
                'content' => [],
                'description' => [],
            ])
            ->create()
            ->bootstrap3Render()
            ->bindEntity($this->articleEntity)
            ->setDefaults($options)
            ->onSuccess[] = function (ArticleOptionsForm $form) {

            $values = $form->getValues( $asArray = true );

            // transform to array
            $options = [];
            foreach ($values as $key => $value) {
                $options[$key] = $value;
            }
            unset($options['id']);


            /** @var ArticleEntity $entity */
            $entity = $form->getEntity();

            $entity->getIdentify()->setOptions($options);

            $form->getEntityMapper()->getEntityManager()->persist($entity);
            $form->getEntityMapper()->getEntityManager()->flush();

            $this->flashMessage("Uloženo", 'success');
            $this->ajaxRedirect('default');
        };

        return $form;
    }


}