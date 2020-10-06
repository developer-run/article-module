<?php
/**
 * This file is part of the devrun
 * Copyright (c) 2016
 *
 * @file    ArticleExtension.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\ArticleModule\DI;

use Devrun\ArticleModule\Entities\ArticleEntity;
use Devrun\Config\CompilerExtension;
use Kdyby\Doctrine\DI\IEntityProvider;
use Kdyby\Doctrine\DI\OrmExtension;
use Kdyby\Events\DI\EventsExtension;
use Nette;
use Nette\DI\ContainerBuilder;
use Nette\DI\Extensions\InjectExtension;

class ArticleExtension extends CompilerExtension implements IEntityProvider
{

    public function loadConfiguration()
    {
        parent::loadConfiguration();

        $builder = $this->getContainerBuilder();
        $config  = $this->getConfig();

        $builder->addDefinition($this->prefix('presenter.translate'))
            ->setFactory('Devrun\CmsModule\ArticleModule\Presenters\TranslatePresenter');

        // forms
        $this->registerForms($builder);

        // repositories
        $this->registerRepositories($builder);

        // facades
        $this->registerFacades($builder);

        // subscribers
        $this->registerSubscribers($builder);
    }

    public function beforeCompile()
    {
        parent::beforeCompile();

        /** @var ContainerBuilder $builder */
        $builder = $this->getContainerBuilder();



        $registerToLatte = function (Nette\DI\Definitions\FactoryDefinition $def) {
            $def->addSetup('?->onCompile[] = function($engine) { Devrun\ArticleModule\Macros\UICmsMacros::install($engine->getCompiler()); }', ['@self']);
        };

        $latteFactoryService = $builder->getByType('Nette\Bridges\ApplicationLatte\ILatteFactory') ?: 'nette.latteFactory';

        if ($builder->hasDefinition($latteFactoryService)) {
            $registerToLatte($builder->getDefinition($latteFactoryService));
        }


    }


    private function registerForms(ContainerBuilder $builder)
    {
        $builder->addFactoryDefinition($this->prefix('form.articleFormFactory'))
            ->setImplement('Devrun\CmsModule\ArticleModule\Forms\IArticleFormFactory')
            ->addTag(InjectExtension::TAG_INJECT);

    }


    private function registerRepositories(ContainerBuilder $builder)
    {
        $builder->addDefinition($this->prefix('repository.articleRepository'))
            ->setFactory('Devrun\ArticleModule\Repositories\ArticleRepository')
            ->addTag(OrmExtension::TAG_REPOSITORY_ENTITY, ArticleEntity::class);

    }


    private function registerFacades(ContainerBuilder $builder)
    {
        $builder->addDefinition($this->prefix('facade.articleFacade'))
            ->setFactory('Devrun\ArticleModule\Facades\ArticleFacade');

        $builder->addDefinition($this->prefix('facade.articlePipe'))
            ->setFactory('Devrun\ArticleModule\Facades\ArticlePipe', [$builder->parameters['autoFlush']])
            ->addTag(EventsExtension::TAG_SUBSCRIBER);

    }


    private function registerSubscribers(ContainerBuilder $builder)
    {
        $builder->addDefinition($this->prefix('listener.article'))
            ->setFactory('Devrun\ArticleModule\Listeners\ArticlePresenterListener')
            ->addTag(EventsExtension::TAG_SUBSCRIBER);

        $builder->addDefinition($this->prefix('listener.route'))
            ->setFactory('Devrun\ArticleModule\Listeners\RouteListener')
            ->addTag(EventsExtension::TAG_SUBSCRIBER);

    }


    /**
     * Returns associative array of Namespace => mapping definition
     *
     * @return array
     */
    function getEntityMappings()
    {
        return array(
            'Devrun\ArticleModule' => dirname(__DIR__) . '/Entities/',
        );
    }

    /**
     * Returns array of ClassNameMask => PresenterNameMask
     *
     * @example return array('*' => 'Booking\*Module\Presenters\*Presenter');
     * @return array
     */
    public function getPresenterMapping()
    {
        return [
            'Article' => "Devrun\\ArticleModule\\*Module\\Presenters\\*Presenter",
        ];
    }
}