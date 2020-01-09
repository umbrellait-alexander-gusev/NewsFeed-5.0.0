<?php

namespace App\Bundle\NewsBundle\Controller;

use App\Bundle\NewsBundle\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class NewsController extends AbstractController
{
    private $entityManager;
    private $knpPaginator;
    private $news;
    private $comment;
    private $likeComment;
    private $category;
    private $user;
    private $security;

    /**
     * Class constructor
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $knpPaginator
     * @param Security $security
     */
    public function __construct(EntityManagerInterface $entityManager, PaginatorInterface $knpPaginator, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->knpPaginator = $knpPaginator;
        $this->security = $security;
        $this->news = $this->entityManager->getRepository('NewsBundle:News');
        $this->comment = $this->entityManager->getRepository('NewsBundle:Comment');
        $this->likeComment = $this->entityManager->getRepository('NewsBundle:LikeComment');
        $this->category = $this->entityManager->getRepository('NewsBundle:Category');
        $this->user = $this->entityManager->getRepository('UserBundle:User');
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
        $comments = $this->comment->findCommentsByNewsId($id);
        $modifiedComments = [];

        foreach ($comments as $key => $comment) {
            $user = $comment->getUser();
            $commentId = $comment->getId();
            $allLikeComment = $this->likeComment->findLikeCommentByCommentId($commentId);
            $countLike = 0;
            $countDislike = 0;

            foreach ($allLikeComment as $likeComment) {
                $likeComment->getLikeComment() ? $countLike++ : $countDislike++;
            }

            $textComment = $comment->getText();
            $dateCreated = $comment->getCreated();
            $authorComment = 'Unregistered User';

            if (isset($user)) {
                $userFirstName = $user->getFirstName();
                $userLastName = $user->getLastName();
                $authorComment = $userFirstName . ' ' . $userLastName;
            }
            $modifiedComments[$key] = [
                'userName' => $authorComment,
                'textComment' => $textComment,
                'countLike' => $countLike,
                'countDislike' => $countDislike,
                'dateCreated' => $dateCreated,
            ];
        }

        usort($modifiedComments, function($a, $b) {
            if ($a['dateCreated'] == $b['dateCreated']) {
                return 0;
            }

            return ($a['dateCreated'] > $b['dateCreated']) ? -1 : 1;
        });

        if (!$newsById) {
            throw $this->createNotFoundException('News not found');
        }

        $nameCategoryBackPage = null;
        if ($queryCategoryName != 'All Category') $nameCategoryBackPage = $queryCategoryName;

        $form = $this->createForm(CommentType::class);
        $form->add('submit', SubmitType::class);
        $form->handleRequest($request);

        $authorizedUser = $this->security->getUser();
//        $IdAuthorizedUser = null;
//        if ($authorizedUser) {
//        }

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();

            $comment->setUser($authorizedUser);
            $comment->setNews($newsById);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Comment adding');
            return $this->redirectToRoute('one_news', ['id' => $id, 'queryCategoryName' => $queryCategoryName]);
        }

        return $this->render('news/oneNews.html.twig', [
            'oneNews' => $newsById,
            'nameCategoryBackPage' => $nameCategoryBackPage,
            'comments' => $modifiedComments,
            'commentForm' => $form->createView(),
        ]);
    }
}
