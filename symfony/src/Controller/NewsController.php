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
     * @param Request $request
     * @return ResponseAlias
     */
    public function getNewsList(Request $request)
    {
        $news = $this
            ->getDoctrine()
            ->getRepository('App:News')
            ->findActive();

        $category = $this->getDoctrine()
            ->getRepository('App:Category')
            ->findAll();

        $newsListForCategory = [];
        // Need for title template
        $titlePage = 'News List';
        // Need for template switching
        $oneCategoryPage = false;
        // Need for query parameter link
        $categoryPageName = '';

        foreach ($category as $categoryItem) {
            $categoryName = $categoryItem->getName();

            foreach ($news as $newsKey => $newsItem) {
                $newsCategoryObj = $newsItem->getCategory();

                if (isset($newsCategoryObj)) {
                    $newsCategory = $newsCategoryObj->getName();

                    if ($categoryName === $newsCategory) {
                        $newsListForCategory[$categoryName][$newsKey] = $newsItem;
                    }
                }
            }
        }

        // That the category 'No category' was always at the end of the list
        foreach ($news as $newsKey => $newsItem) {
            $newsCategoryObj = $newsItem->getCategory();
            if (!isset($newsCategoryObj))  $newsListForCategory['No category'][$newsKey] = $newsItem;
        }

        if (count($request->query) > 0) {
            $queryCategoryName = $request->query->get('queryCategoryName');
            if (isset($queryCategoryName)) {
                $newsListForCategory = $newsListForCategory[$queryCategoryName];
                $oneCategoryPage = true;
                $titlePage = 'News category: ' . $queryCategoryName;
                $categoryPageName = $queryCategoryName;
            }
        }

        return $this->render('news/newsList.html.twig', [
            'newsListForCategory' => $newsListForCategory,
            'oneCategoryPage' => $oneCategoryPage,
            'titlePage' => $titlePage,
            'categoryPageName' => $categoryPageName,
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

        $nameCategoryBackPage = null;
        if (count($request->query) > 0) {
            $queryCategoryName = $request->query->get('queryCategoryName');
            if (isset($queryCategoryName)) {
                $nameCategoryBackPage = $queryCategoryName;
            }
        }

        return $this->render('news/oneNews.html.twig', [
            'oneNews' => $news,
            'nameCategoryBackPage' => $nameCategoryBackPage,
        ]);
    }
}
