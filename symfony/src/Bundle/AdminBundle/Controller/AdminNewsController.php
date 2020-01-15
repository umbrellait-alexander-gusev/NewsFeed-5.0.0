<?php

namespace App\Bundle\AdminBundle\Controller;

use App\Bundle\NewsBundle\Form\NewsType;
use ArrayObject;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/admin")
 */
class AdminNewsController extends AbstractController
{
    private $entityManager;
    private $knpPaginator;
    private $news;
    private $category;
    private $comment;
    private $likeComment;

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
        $this->comment = $this->entityManager->getRepository('NewsBundle:Comment');
        $this->likeComment = $this->entityManager->getRepository('NewsBundle:LikeComment');
    }

    /**
     * Get news list
     *
     * @Route("/news_list", name="admin_news_list")
     * @param Request $request
     * @return Response
     */
    public function getNewsList(Request $request)
    {
        $newsList = $this->news->findAll();
        $categoryList = [];

        foreach ($newsList as $news) {
            $newsId = $news->getId();
            $categoryObj = $news->getCategory();

            $categoryName = 'No category';
            if (isset($categoryObj)) $categoryName = $categoryObj->getName();

            $categoryList[$newsId] = $categoryName;
        }

        $pagination = $this->knpPaginator->paginate(
            $newsList,
            $request->query->getInt('page', 1),
            10
        );

        return $this->render('admin/news/newsList.html.twig', [
            'newsList' => $pagination,
            'categoryList' => $categoryList,
        ]);
    }

    /**
     * Adding one news
     *
     * @Route("/add_news", name="admin_add_news")
     * @param Request $request
     * @return RedirectResponse|Response
     */
    public function addNews(Request $request)
    {
        $categories = $this->category->findAll();

        $categoryChoices = new ArrayObject();
        foreach ($categories as $category) {
            $categoryChoices->offsetSet($category->getName(), $category->getId());
        }

        $categoryChoices->offsetSet('No category', null);
        $categoryList = ['choices' => $categoryChoices];

        $form = $this->createForm(NewsType::class);
        $form->add('category', ChoiceType::class, $categoryList);
        $form->add('submit', SubmitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news = $form->getData();

            foreach ($categories as $category) {
                if ($news->getCategory() === $category->getId()) {
                    $news->setCategory($category);
                }
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($news);
            $entityManager->flush();

            $this->addFlash('success', 'Saved news');
            return $this->redirectToRoute('admin_add_news');
        }

        return $this->render('admin/news/addNews.html.twig', [
            'newsForm' => $form->createView()
        ]);
    }

    /**
     * Edit news
     *
     * @Route("/edit_news/{id}", name="admin_edit_news")
     * @param Request $request
     * @param $id
     * @return Response
     */
    public function editNews(Request $request, $id)
    {
        $newsById = $this->news->find($id);
        $categories = $this->category->findAll();

        $categoryChoices = new ArrayObject();

        foreach ($categories as $category) {
            $categoryChoices->offsetSet($category->getName(), $category->getId());
        }

        $categoryChoices->offsetSet('No category', null);
        $categoryList = ['choices' => $categoryChoices];

        $form = $this->createForm(NewsType::class, $newsById);
        $form->add('category', ChoiceType::class, $categoryList);
        $form->add('submit', SubmitType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $news = $form->getData();

            foreach ($categories as $category) {
                if ($news->getCategory() === $category->getId()) {
                    $news->setCategory($category);
                }
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($news);
            $entityManager->flush();

            $this->addFlash('success', 'Changes saved successfully');
        }

        return $this->render('admin/news/editNews.html.twig', [
            'newsForm' => $form->createView(),
            'news' => $newsById,
        ]);
    }

    /**
     * Delete news
     *
     * @Route("/delete_news/{id}", name="admin_delete_news")
     * @param $id
     * @return RedirectResponse
     */
    public function deleteNews($id)
    {
        $newsById = $this->news->find($id);

        if (!$newsById) {
            throw $this->createNotFoundException('News not found');
        }

        $entityManager = $this->getDoctrine()->getManager();

        $comments = $this->comment->getCommentsByNewsId($id);

        foreach ($comments as $comment) {
            $commentId = $comment->getId();
            $allLikeComment = $this->likeComment->findLikeCommentByCommentId($commentId);

            foreach ($allLikeComment as $likeComment) {
                $entityManager->remove($likeComment);
            }

            $entityManager->remove($comment);
        }

        $entityManager->remove($newsById);
        $entityManager->flush();

        $this->addFlash('danger', 'News successfully deleted');
        return $this->redirectToRoute('admin_news_list');
    }

    /**
     * Change active news
     *
     * @Route("/change_active_news/{id}/{active}", name="admin_change_active_news")
     * @param $id
     * @param $active
     * @return JsonResponse
     */
    public function changeActiveNews(int $id, $active)
    {
        $newsById = $this->news->find($id);

        if (!$newsById) {
            throw $this->createNotFoundException('News not found');
        }

        $newsById->setActive($active == 'true');

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($newsById);
        $entityManager->flush();

        return new JsonResponse(['News active' => 'Successful']);
    }
}
