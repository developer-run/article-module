<?php
/**
 * This file is part of devrun.
 * Copyright (c) 2017
 *
 * @file    TArticlesPipe.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\ArticleModule\Presenters;

use Devrun\ArticleModule\Facades\ArticlePipe;

trait TArticlesPipe
{

    /** @var ArticlePipe */
    public $articlePipe;


    public function injectArticlePipe(ArticlePipe $articlePipe) {
        $this->articlePipe = $articlePipe;
        $this->template->_articlePipe = $articlePipe;
    }



}