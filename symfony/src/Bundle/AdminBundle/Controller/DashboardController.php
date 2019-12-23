<?php

namespace App\Bundle\AdminBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function getDashboardInfo()
    {
        $newsList = $this
            ->getDoctrine()
            ->getRepository('App:News')
            ->findAll();

        $newsActiveList = $this
            ->getDoctrine()
            ->getRepository('App:News')
            ->findActive();

        $newsCount = count($newsList);
        $newsActiveCount = count($newsActiveList);

        $categoryList = $this
            ->getDoctrine()
            ->getRepository('App:Category')
            ->findAll();

        $categoryCount = [];
        $categoryListId = [];

        foreach ($newsList as $key => $news) {
            $categoryObj = $news->getCategory();

            $categoryId = null;
            if (isset($categoryObj)) {
                $categoryId = $categoryObj->getId();
            }

            $categoryListId[$key] = $categoryId;
        }

        $categoryUniqueListId = array_unique($categoryListId);

        foreach ($categoryUniqueListId as $categoryUniqueId) {
            $categoryListSortById = $this
                ->getDoctrine()
                ->getRepository('App:News')
                ->getCountNewsByCategory($categoryUniqueId);

            $categoryObj = $this
                ->getDoctrine()
                ->getRepository('App:Category')
                ->findCategoryById($categoryUniqueId);

            if (count($categoryObj) > 0) {
                $categoryName = $categoryObj[0]->getName();
                $categoryCount[$categoryName] = count($categoryListSortById);
            } else {
                $categoryCount['No category'] = count($categoryListSortById);
            }
        }

        return $this->render('admin/dashboard/index.html.twig', [
            'newsList' => $newsList,
            'newsCount' => $newsCount,
            'newsActiveCount' => $newsActiveCount,
            'categoryList' => $categoryList,
            'categoryCount' => $categoryCount
        ]);
    }
}
