<?php

namespace App\Bundle\NewsBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{
    private $entityManager;
    private $knpPaginator;
    private $news;
    private $category;

    /**
     * Class constructor
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $knpPaginator
     */
    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $knpPaginator)
    {
        $this->entityManager = $entityManager;
        $this->knpPaginator = $knpPaginator;
        $this->news = $this->entityManager->getRepository('NewsBundle:News');
        $this->category = $this->entityManager->getRepository('NewsBundle:Category');
    }

    /**
     * @Route("/news_list", name="news")
     * @param Request $request
     * @return ResponseAlias
     */
    public function getNewsList(Request $request)
    {
        $activeNews = $this->news->findActive();
        $categories = $this->category->findAll();

        $newsListForCategory = [];
        // Need for title template
        $titlePage = 'News List';
        // Need for template switching
        $oneCategoryPage = false;
        // Need for query parameter link
        $categoryPageName = '';

        foreach ($categories as $categoryItem) {
            $categoryName = $categoryItem->getName();

            foreach ($activeNews as $newsKey => $newsItem) {
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
        foreach ($activeNews as $newsKey => $newsItem) {
            $newsCategoryObj = $newsItem->getCategory();
            if (!isset($newsCategoryObj))  $newsListForCategory['No category'][$newsKey] = $newsItem;
        }

        if (count($request->query) > 0) {
            $queryCategoryName = $request->query->get('queryCategoryName');
            if (isset($queryCategoryName)) {
                $newsListForCategory = $newsListForCategory[$queryCategoryName];

                $newsListForCategory = $this->knpPaginator->paginate(
                    $newsListForCategory,
                    $request->query->getInt('page', 1),
                    9
                );

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
     * @Route("/one_news/{id}/{queryCategoryName}", name="one_news")
     * @param Request $request
     * @param $id
     * @param $queryCategoryName
     * @return ResponseAlias
     */
    public function getOneNews(Request $request, $id, $queryCategoryName)
    {
        $newsById = $this->news->find($id);

        if (!$newsById) {
            throw $this->createNotFoundException('News not found');
        }

        $nameCategoryBackPage = null;
        if ($queryCategoryName != 'All Category') $nameCategoryBackPage = $queryCategoryName;

        return $this->render('news/oneNews.html.twig', [
            'oneNews' => $newsById,
            'nameCategoryBackPage' => $nameCategoryBackPage,
        ]);
    }
}
