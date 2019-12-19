<?php

namespace App\Controller;

use App\Form\NewsType;
use ArrayObject;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{
    /**
     * @Route("/news_list", name="news")
     */
    public function newsListAction()
    {
        $news = $this
            ->getDoctrine()
            ->getRepository('App:News')
            ->findActive();

        $category = $this->getDoctrine()
            ->getRepository('App:Category')
            ->findAll();

        return $this->render('news/newsList.html.twig', [
            'news_list' => $news,
            'category_list' => $category
        ]);
    }

    /**
     * @Route("/news_list/{id}", name="news_item")
     * @param Request $request
     * @return ResponseAlias
     */
    public function oneNewsAction(Request $request)
    {
        $newsId = $request->get('id');
        $news = $this
            ->getDoctrine()
            ->getRepository('App:News')
            ->find($newsId);

        if (!$news) {
            throw $this->createNotFoundException('News not found');
        }

        return $this->render('news/oneNews.html.twig', [
            'one_news' => $news,
        ]);
    }

    /**
     * @Route("/add_news", name="add_news")
     * @param Request $request
     * @return RedirectResponse|ResponseAlias
     */
    public function addNewsAction(Request $request)
    {
        $categories = $this
            ->getDoctrine()
            ->getRepository('App:Category')
            ->findAll();

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

            $em = $this->getDoctrine()->getManager();
            $em->persist($news);
            $em->flush();

            $this->addFlash('success', 'Saved new news');
            return $this->redirectToRoute('add_news');
        }

        return $this->render('news/addNews.html.twig', [
            'news_form' => $form->createView()
        ]);
    }
}
