<?php
/**
 * This file is part of the nova.superletuska.cz
 * Copyright (c) 2016
 *
 * @file    ArticleForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\ArticleModule\Forms;

use Devrun\CmsModule\Forms\DevrunForm;

interface IArticleFormFactory
{
    /** @return ArticleForm */
    function create();
}

class ArticleForm extends DevrunForm implements IArticleFormFactory
{

    protected $labelControlClass = 'div class="col-sm-2 control-label"';

    protected $controlClass = 'div class=col-sm-10';


    private $editReference = false;

    private $options = [];



    /** @return ArticleForm */
    function create()
    {
        if ($this->isEnable("header") ) {
            if ($this->isInline('header')) {
                $item = $this->addHidden('header');
                if ($this->editReference) $item = $this->addHidden('refHeader');

            } else {
                $item = $this->addTextArea('header', 'Titulek', null, 8);
                $item->setHtmlAttribute('class', "editable");
                $item->setHtmlAttribute('placeholder', "titulek článku")
                    ->setMaxLength(255);

                if ($this->editReference) {
                    $item = $this->addTextArea('refHeader', 'ref Titulek', null, 8);
                    $item->setHtmlAttribute('class', "editable");
                    $item->setHtmlAttribute('placeholder', "titulek článku")
                        ->setMaxLength(255);
                }
            }

        }

        if ($this->isEnable("subHeader") ) {
            if ($this->isInline('subHeader')) {
                $item = $this->addHidden('subHeader');
                if ($this->editReference) $item = $this->addHidden('refSubHeader');

            } else {
                $item = $this->addHidden('subHeader');
                if ($this->editReference) $item = $this->addHidden('refSubHeader');

//                $item = $this->addTextArea('subHeader', 'Podtitulek', null, 8);
//                $item->setHtmlAttribute('class', "editable");
//                $item->setHtmlAttribute('placeholder', "Podtitulek článku")
//                    ->setMaxLength(255);
//
//                if ($this->editReference) {
//                    $item = $this->addTextArea('refSubHeader', 'ref Podtitulek', null, 8);
//                    $item->setHtmlAttribute('class', "editable");
//                    $item->setHtmlAttribute('placeholder', "Podtitulek článku")
//                        ->setMaxLength(255);
//                }
            }

        }

        if ($this->isEnable("perex") ) {
            if ($this->isInline('perex')) {
                $item = $this->addHidden('perex');
                if ($this->editReference) $item = $this->addHidden('refPerex');

            } else {
                $item = $this->addTextArea('perex', 'Perex článku', null, 8);
                $item->setHtmlAttribute('class', "editable");
                $item->setHtmlAttribute('placeholder', "krátký popis článku")
                    ->setMaxLength(65535);

                if ($this->editReference) {
                    $item = $this->addTextArea('refPerex', 'ref Perex článku', null, 8);
                    $item->setHtmlAttribute('class', "editable");
                    $item->setHtmlAttribute('placeholder', "krátký popis článku")
                        ->setMaxLength(65535);
                }
            }


        }

        if ($this->isEnable("content") ) {
            if ($this->isInline('content')) {
                $item = $this->addHidden('content');
                if ($this->editReference) $item = $this->addHidden('refContent');

            } else {
                $item = $this->addTextArea('content', 'Text článku', null, 8);
                $item->setHtmlAttribute('class', "editable");

                if ($this->editReference) {
                    $item = $this->addTextArea('refContent', 'ref Text článku', null, 8);
                    $item->setHtmlAttribute('class', "editable");
                }
            }

        }

        if ($this->isEnable("description") ) {
            if ($this->isInline('description')) {
                $item = $this->addHidden('description');
                if ($this->editReference) $item = $this->addHidden('refDescription');

            } else {
                $item = $this->addTextArea('description', 'Poznámka', null, 8);
                $item->setHtmlAttribute('class', "editable");

                if ($this->editReference) {
                    $item = $this->addTextArea('refDescription', 'ref Poznámka', null, 8);
                    $item->setHtmlAttribute('class', "editable");
                }
            }

            $item->setMaxLength(65535);
        }

        if ($this->isAnyEnable()) {
            $this->addSubmit('send', 'Upravit');
            $this->onSuccess[] = [$this, 'success'];
        }

        $this->addFormClass(['ajax']);

        return $this;
    }


    public function success(ArticleForm $form, $values)
    {
        $entity = $form->getEntity();
        $ignore = ['id'];
        foreach ($values as $key => $value) {
            if (isset($entity->$key) && !in_array($key, $ignore)) {
                $entity->$key = $value;
            }
        }



    }


    private function isAnyEnable()
    {
        $result = false;
        foreach ($this->options as $column => $option) {
            if ($this->isEnable($column)) {
                $result = true;
                break;
            }
        }

        return $result;
    }

    /**
     * @example old code [return isset($this->options[$column]['enable']) && $this->options[$column]['enable'];]
     * @param $column
     * @return bool
     */
    private function isEnable($column)
    {
        return isset($this->options[$column]);
    }

    private function isInline($column)
    {
        return isset($this->options[$column]['type']) && $this->options[$column]['type'] == 'inline';
    }

    private function isOutline($column)
    {
        return isset($this->options[$column]['type']) && $this->options[$column]['type'] == 'outline';
    }



    /**
     * @param array $options
     *
     * @return $this
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
        return $this;
    }


    /**
     * @param bool $editReference
     *
     * @return $this
     */
    public function setEditReference(bool $editReference)
    {
        $this->editReference = $editReference;
        return $this;
    }




}