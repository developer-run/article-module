<?php


namespace Devrun\ArticleModule\Repositories;

use Devrun\Tests\BaseTestCase;

class ArticleRepositoryTest extends BaseTestCase
{

    /** @var \Devrun\ArticleModule\Repositories\ArticleRepository @inject */
    public $articleRepository;


    public function testName()
    {
        $this->assertFalse(false);



        dump($this->articleRepository->getClassName());
    }


}