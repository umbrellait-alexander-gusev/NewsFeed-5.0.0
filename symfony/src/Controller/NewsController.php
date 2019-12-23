<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{
    /**
     * @Route("/news_list", name="news")
     */
    public function getNewsList()
    {
        $news = $this
            ->getDoctrine()
            ->getRepository('App:News')
            ->findActive();

        $category = $this->getDoctrine()
            ->getRepository('App:Category')
            ->findAll();

        return $this->render('news/newsList.html.twig', [
            'newsList' => $news,
            'categoryList' => $category
        ]);
    }

    /**
     * @Route("/one_news/{id}", name="one_news")
     * @param Request $request
     * @param $id
     * @return ResponseAlias
     */
    public function getOneNews(Request $request, $id)
    {
        $news = $this
            ->getDoctrine()
            ->getRepository('App:News')
            ->find($id);

        if (!$news) {
            throw $this->createNotFoundException('News not found');
        }

        return $this->render('news/oneNews.html.twig', [
            'oneNews' => $news,
        ]);
    }
}
