<?php

    namespace App\Controller;

    use App\Entity\Todo;
    use App\Form\TodoType;
    use Doctrine\Persistence\ManagerRegistry;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Routing\Annotation\Route;
    use Symfony\Component\String\Slugger\SluggerInterface;
    use function Symfony\Component\Form\isValid;
    use Doctrine\DBAL\Type\Type;
    use Doctrine\ORM\EntityManagerInterface;
    use App\Controller\TodoController;


    #[Route ('/')]
    class TodoController extends AbstractController
    {
        #[Route('/to_do', name: 'todo_list')]
        public function listAction(EntityManagerInterface $entityManager): Response
        {
            $todos = $entityManager
                ->getRepository(Todo::class)
                ->findAll();

            return $this->render('todo/index.html.twig', [
                'todos' => $todos,
            ]);
        }

        #[Route("/to_do/details/{id}", name:"todo_details")]
        public function detailsAction(EntityManagerInterface $entityManager, $id): Response
        {
            $todos = $entityManager
                ->getRepository(Todo::class)
                ->find($id);
            return $this->render('todo/details.html.twig', ['todos' => $todos]);
        }

        #[Route("/to_do/delete/{id}", name: 'todo_delete')]
        public function deleteAction(ManagerRegistry $doctrine, $id)
        {
            $entityManager = $doctrine->getManager();
            $Todo = $entityManager->getRepository(Todo::class)->find($id);
            if (!$Too) {
                $this->addFlash(
                    'error',
                    'Todo not found'
                );
                return $this->redirectToRoute('todo_list');
            }
            $entityManager->remove($Todo);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'Too deleted'
            );
            return $this->redirectToRoute('todo_list');
        }

        #[Route("/to_do/create", name: 'todo_create', methods: ['GET','POST'])]
        public function createAction(ManagerRegistry $doctrine, Request $request, SluggerInterface $slugger): Response
        {
            $Todo = new Todo();
            $form = $this->createForm(TodoType::class, $Todo);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {

                $entityManager = $doctrine->getManager();
                $entityManager->persist($Todo);
                $entityManager->flush();

                $this->addFlash('success', 'Too create');
                return $this->redirectToRoute('todo_list');
            }

            return $this->renderForm('todo/create.html.twig', ['form' => $form,]);
        }

        public function saveChanges($form, $request, $Todo)
        {
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $Todo->setName($request->request->get('Todo')['name']);
                $Todo->setCategory($request->request->get('Todo')['category']);
                $Todo->setDescription($request->request->get('Todo')['description']);
                $Todo->setPriority($request->request->get('Todo')['priority']);
                $Todo->setDueDate(\DateTime::createFromFormat('Y-m-d', $request->request->get('Todo')['due_date']));
                $em = $this->getDoctrine()->getManager();
                $em->persist($Todo);
                $em->flush();

                return true;
            }

            return false;
        }

        #[Route("/to_do/edit/{id}", name:"todo_edit", methods: ['GET', 'POST'])]

        public function editAction(ManagerRegistry $doctrine, $id, Request $request, SluggerInterface $slugger): Response
        {
            $entityManager = $doctrine->getManager();
            $Todo = $entityManager->getRepository(Todo::class)->find($id);
            $form = $this->createForm(TodoType::class, $Todo);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $em = $doctrine->getManager();
                $em->persist($Todo);
                $em->flush();

                return $this->redirectToRoute('todo_list', [
                    'id' => $Todo->getId()
                ]);
            }

            return $this->render('todo/edit.html.twig', [
                'form' => $form->createView()
            ]);
        }
    }