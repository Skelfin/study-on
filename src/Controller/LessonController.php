<?php

namespace App\Controller;

use App\Entity\Lesson;
use App\Entity\Course;
use App\Form\LessonType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use App\Service\BillingClient;

#[Route('/lessons')]
class LessonController extends AbstractController
{

    private BillingClient $billingClient;

    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    #[Route('/new', name: 'lesson_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $courseId = $request->query->get('course_id');
        $course = $entityManager->getRepository(Course::class)->find($courseId);

        if (!$course) {
            throw $this->createNotFoundException('Course not found');
        }

        $lesson = new Lesson();
        $lesson->setCourse($course);

        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lesson);
            $entityManager->flush();

            return $this->redirectToRoute('course_show', ['id' => $lesson->getCourse()->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lesson/new.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'lesson_show', methods: ['GET'])]
    public function show(Lesson $lesson): Response
    {
        $course = $lesson->getCourse();

        if ($course->getPrice() > 0) {
            /** @var \App\Security\User $user */
            $user = $this->getUser();

            if (!$user || !$user->getApiToken()) {
                throw new AccessDeniedException('Вы должны войти в систему, чтобы просмотреть этот урок.');
            }

            $hasAccess = false;

            try {
                $transactions = $this->billingClient->getTransactionHistory($user->getApiToken());

                foreach ($transactions as $transaction) {
                    if ($transaction['type'] === 'payment' && $transaction['course_code'] === $course->getCode()) {
                        if (isset($transaction['expires_at'])) {
                            $expiresAt = new \DateTime($transaction['expires_at']);
                            $now = new \DateTime();
                            if ($expiresAt > $now) {
                                $hasAccess = true;
                                break;
                            }
                        } else {
                            $hasAccess = true;
                            break;
                        }
                    }
                }
            } catch (\Exception $e) {
                throw new AccessDeniedException('Не удалось проверить доступ к уроку. Пожалуйста, попробуйте позже.');
            }

            if (!$hasAccess) {
                throw new AccessDeniedException('У вас нет доступа к этому уроку.');
            }
        }

        return $this->render('lesson/show.html.twig', [
            'lesson' => $lesson,
        ]);
    }

    #[Route('/{id}/edit', name: 'lesson_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(LessonType::class, $lesson);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('lesson_show', ['id' => $lesson->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('lesson/edit.html.twig', [
            'lesson' => $lesson,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'lesson_delete', methods: ['POST'])]
    public function delete(Request $request, Lesson $lesson, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $lesson->getId(), $request->getPayload()->getString('_token'))) {
            $courseId = $lesson->getCourse()->getId();
            $entityManager->remove($lesson);
            $entityManager->flush();

            return $this->redirectToRoute('course_show', ['id' => $courseId], Response::HTTP_SEE_OTHER);
        }

        return $this->redirectToRoute('lesson_index', [], Response::HTTP_SEE_OTHER);
    }
}