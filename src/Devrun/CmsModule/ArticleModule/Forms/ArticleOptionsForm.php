<?php
/**
 * This file is part of souteze.pixman.cz.
 * Copyright (c) 2019
 *
 * @file    ArticleOptionsForm.php
 * @author  Pavel Paulík <pavel.paulik@support.etnetera.cz>
 */

namespace Devrun\CmsModule\ArticleModule\Forms;

use Devrun\CmsModule\Forms\DevrunForm;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class ArticleOptionsForm extends DevrunForm
{

    /** @var array */
    private $options = [];


    function create()
    {

//        $options = $this->addContainer('options');

        foreach ($this->options as $column => $option) {

//            dump($column);
//            dump($option);
//            die();
            $this->addGroup($column);

            $item = $this->addContainer($column);

            $item->addCheckbox('enable', "$column Enable");


            $item->addSelect('type', 'Type', ['inline' => 'Inline', 'outline' => 'Outline'])
                ->setPrompt('--vyberte--');

            $item->addSelect('editor', 'Editor', ['simple' => 'Simple', 'text' => 'Text'])
                ->setPrompt('--vyberte--');

        }

//        $item = $this->addContainer('perex');

//        dump($item);

//        $item->addText('asdw', 'sdwd');



        $this->addSubmit('send', 'Upravit');
        $this->onSuccess[] = [$this, 'success'];

//        $this->addFormClass(['ajax']);

        return $this;
    }


    public function success($form)
    {
//        dump($form->getValues(true)
//        );
//        die();



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






}