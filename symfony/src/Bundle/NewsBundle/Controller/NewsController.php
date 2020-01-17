<?php

namespace App\Bundle\NewsBundle\Controller;

use App\Bundle\NewsBundle\Entity\LikeComment;
use App\Bundle\NewsBundle\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use ProxyManager\Factory\RemoteObject\Adapter\JsonRpc;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;
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
        $authorizedUser = $this->security->getUser();
        $newsById = $this->news->find($id);
        $comments = $this->comment->getCommentsByNewsId($id);
        $modifiedComments = [];

        foreach ($comments as $key => $comment) {
            $user = $comment->getUser();
            $commentId = $comment->getId();
            $allLikeComment = $this->likeComment->findLikeCommentByCommentId($commentId);
            $countLike = 0;
            $countDislike = 0;

            $commentId = $comment->getId();
            $textComment = $comment->getText();
            $dateCreated = $comment->getCreated();
            $authorComment = 'Unregistered User';

            if (isset($user)) {
                $userFirstName = $user->getFirstName();
                $userLastName = $user->getLastName();
                $authorComment = $userFirstName . ' ' . $userLastName;
            }

            $commentActive = false;
            $selectedLikeCommentType = null;

            foreach ($allLikeComment as $likeComment) {
                $authorIdLikeComment = $likeComment->getUser()->getId();

                if (isset($authorizedUser)) {
                    if ($authorizedUser->getId() == $authorIdLikeComment) {

                        $commentActive = true;
                    }
                }

                if ($likeComment->getLikeComment() === true) {
                    $countLike++;

                } else if ($likeComment->getLikeComment() === false) {
                    $countDislike++;
                }

                $selectedLikeCommentType = $likeComment->getLikeComment();
            }

            $modifiedComments[$key] = [
                'userName' => $authorComment,
                'commentId' => $commentId,
                'textComment' => $textComment,
                'countLike' => $countLike,
                'countDislike' => $countDislike,
                'dateCreated' => $dateCreated,
                'commentActive' => $commentActive,
                'selectedLikeCommentType' => $selectedLikeCommentType,
            ];
        }

        usort($modifiedComments, function($a, $b) {
            if ($a['dateCreated'] == $b['dateCreated']) {
                return 0;
            }

            return ($a['dateCreated'] > $b['dateCreated']) ? -1 : 1;
        });

        $paginationComments = $this->knpPaginator->paginate(
            $modifiedComments,
            $request->query->getInt('page', 1),
            5
        );

        if (!$newsById) {
            throw $this->createNotFoundException('News not found');
        }

        $nameCategoryBackPage = null;
        if ($queryCategoryName != 'All Category') $nameCategoryBackPage = $queryCategoryName;

        $form = $this->createForm(CommentType::class);
        $form->add('submit', SubmitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment = $form->getData();

            $comment->setUser($authorizedUser);
            $comment->setNews($newsById);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($comment);
            $entityManager->flush();

            $this->addFlash('success', 'Your comment successful adding');
            return $this->redirectToRoute('one_news', ['id' => $id, 'queryCategoryName' => $queryCategoryName]);
        }

        return $this->render('news/oneNews.html.twig', [
            'oneNews' => $newsById,
            'nameCategoryBackPage' => $nameCategoryBackPage,
            'comments' => $paginationComments,
            'commentForm' => $form->createView(),
        ]);
    }

    /**
     * Change active news
     *
     * @Route("/change_like_comment/{commentId}/{likeCommentType}", name="change_like_comment")
     * @param $commentId
     * @param $likeCommentType
     * @return JsonResponse
     */
    public function changeLikeComment(int $commentId, int $likeCommentType)
    {
        $authorizedUser = $this->security->getUser();
        $likeComment = new LikeComment;

        if (isset($authorizedUser)) {
            $userId = $authorizedUser->getId();
            $user = $this->user->findById($userId);
            $comment = $this->comment->findById($commentId);

            $likeComment->setUser($user[0]);
            $likeComment->setComment($comment[0]);
            $likeComment->setLikeComment($likeCommentType);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($likeComment);
            $entityManager->flush();
        }

        return new JsonResponse(['Successful']);
    }
}
