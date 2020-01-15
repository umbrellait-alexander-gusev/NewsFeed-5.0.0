<?php

namespace App\Bundle\AdminBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    private $entityManager;
    private $knpPaginator;
    private $news;
    private $category;
    private $uesr;

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
        $this->uesr = $this->entityManager->getRepository('UserBundle:User');
    }
    
    /**
     * @Route("/admin", name="admin")
     */
    public function getDashboardInfo()
    {
        $newsList = $this->news->findAll();
        $activeNews = $this->news->findActive();
        $categoryList = $this->category->findAll();
        $userList = $this->uesr->findAll();
        $userRolesCount = [
            'Admin' => 0,
            'User' => 0,
        ];

        foreach($userList as $user) {
            $userRole = $user->getRoles();
            $flipUserRole = array_flip($userRole);

            if (array_key_exists('ROLE_ADMIN', $flipUserRole)) {
                $userRolesCount['Admin']++;
            } else {
                $userRolesCount['User']++;
            }
        }

        $newsCount = count($newsList);
        $newsActiveCount = count($activeNews);

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
            $categoryListSortById = $this->news->getCountNewsByCategory($categoryUniqueId);
            $categoryObj = $this->category->findCategoryById($categoryUniqueId);

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
            'categoryCount' => $categoryCount,
            'userList' => $userList,
            'userRolesCount' => $userRolesCount,
        ]);
    }
}
