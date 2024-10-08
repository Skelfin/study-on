<?php

namespace App\Controller;

use App\Entity\Course;
use App\Form\CourseType;
use App\Service\BillingClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Exception\InsufficientFundsException;

#[Route('/courses')]
class CourseController extends AbstractController
{
    private BillingClient $billingClient;

    public function __construct(BillingClient $billingClient)
    {
        $this->billingClient = $billingClient;
    }

    #[Route('/', name: 'course_index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $courses = $entityManager->getRepository(Course::class)->findAll();
        /** @var \App\Security\User $user */
        $user = $this->getUser();
        $purchasedCourses = [];
        $userBalance = null;

        if ($user && $user->getApiToken()) {
            try {
                $purchasedCourses = $this->getPurchasedCourses($user->getApiToken());
                $userBalance = $this->billingClient->getCurrentUser($user->getApiToken())['balance'];
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Не удалось получить данные о покупках.');
            }
        }

        return $this->render('course/index.html.twig', [
            'courses'          => $courses,
            'purchasedCourses' => $purchasedCourses,
            'userBalance'      => $userBalance,
        ]);
    }

    #[Route('/new', name: 'course_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $course = new Course();
        $course->setCode('temp_value');

        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($course);
            $entityManager->flush();

            $course->setCode($course->getId() . 'course');
            $entityManager->flush();

            /** @var \App\Security\User $user */
            $user = $this->getUser();

            try {
                $this->billingClient->createCourseInBilling($course, $user->getApiToken());
                $this->addFlash('success', 'Курс успешно создан и добавлен в биллинг.');
            } catch (\Exception $e) {
                $entityManager->remove($course);
                $entityManager->flush();

                $this->addFlash('danger', 'Не удалось создать курс в биллинге: ' . $e->getMessage());
                return $this->redirectToRoute('course_new');
            }

            return $this->redirectToRoute('course_index');
        }

        return $this->render('course/new.html.twig', [
            'course' => $course,
            'form'   => $form,
        ]);
    }

    #[Route('/{id}', name: 'course_show', methods: ['GET'])]
    public function show(Course $course): Response
    {
        /** @var \App\Security\User $user */
        $user = $this->getUser();
        $hasAccess = false;
        $insufficientFunds = false;

        if ($user && $user->getApiToken()) {
            try {
                $purchasedCourses = $this->getPurchasedCourses($user->getApiToken());
                $hasAccess = array_key_exists($course->getCode(), $purchasedCourses);
                $userBalance = $this->billingClient->getCurrentUser($user->getApiToken())['balance'];

                if ($userBalance < $course->getPrice()) {
                    $insufficientFunds = true;
                }
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Не удалось получить данные о покупке.');
            }
        }

        return $this->render('course/show.html.twig', [
            'course'           => $course,
            'hasAccess'        => $hasAccess,
            'insufficientFunds' => $insufficientFunds,
        ]);
    }

    #[Route('/{id}/edit', name: 'course_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Security\User $user */
        $user = $this->getUser();

        $form = $this->createForm(CourseType::class, $course);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $entityManager->flush();
                $this->billingClient->updateCourseInBilling($course, $user->getApiToken());

                $this->addFlash('success', 'Курс успешно обновлён и изменения отправлены в биллинг.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Не удалось обновить курс в биллинге: ' . $e->getMessage());
                return $this->redirectToRoute('course_edit', ['id' => $course->getId()]);
            }

            return $this->redirectToRoute('course_show', ['id' => $course->getId()]);
        }

        return $this->render('course/edit.html.twig', [
            'course' => $course,
            'form'   => $form,
        ]);
    }

    #[Route('/{id}', name: 'course_delete', methods: ['POST'])]
    public function delete(Request $request, Course $course, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Security\User $user */
        $user = $this->getUser();

        if ($this->isCsrfTokenValid('delete' . $course->getId(), $request->request->get('_token'))) {
            try {
                $this->billingClient->deleteCourseInBilling($course->getCode(), $user->getApiToken());
                $entityManager->remove($course);
                $entityManager->flush();

                $this->addFlash('success', 'Курс успешно удалён.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Не удалось удалить курс: ' . $e->getMessage());
                return $this->redirectToRoute('course_show', ['id' => $course->getId()]);
            }
        } else {
            $this->addFlash('danger', 'Недействительный CSRF токен.');
        }

        return $this->redirectToRoute('course_index');
    }

    #[Route('/pay/{code}', name: 'course_pay', methods: ['POST'])]
    public function pay(string $code, Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Security\User $user */
        $user = $this->getUser();
        $course = $entityManager->getRepository(Course::class)->findOneBy(['code' => $code]);

        if (!$course) {
            $this->addFlash('danger', 'Курс не найден.');
            return $this->redirectToRoute('course_index');
        }

        if (!$user || !$user->getApiToken()) {
            $this->addFlash('warning', 'Необходимо авторизоваться для покупки курса.');
            return $this->redirectToRoute('app_login');
        }

        try {
            $type = $request->request->get('type');
            $this->billingClient->payCourse($code, $user->getApiToken(), $type);

            $this->addFlash('success', 'Курс успешно оплачен.');
        } catch (InsufficientFundsException $e) {
            $this->addFlash('danger', 'Недостаточно средств для оплаты курса.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Не удалось приобрести курс: ' . $e->getMessage());
        }

        return $this->redirectToRoute('course_show', ['id' => $course->getId()]);
    }

    private function getPurchasedCourses(string $apiToken): array
    {
        $transactions = $this->billingClient->getTransactionHistory($apiToken);
        $purchasedCourses = [];

        foreach ($transactions as $transaction) {
            if ($transaction['type'] === 'payment' && isset($transaction['course_code'])) {
                $courseCode = $transaction['course_code'];
                $expiresAt = $transaction['expires_at'] ?? null;

                if (isset($purchasedCourses[$courseCode])) {
                    if ($expiresAt && new \DateTime($expiresAt) > new \DateTime($purchasedCourses[$courseCode]['expires_at'] ?? 'now')) {
                        $purchasedCourses[$courseCode]['expires_at'] = $expiresAt;
                    }
                } else {
                    $purchasedCourses[$courseCode] = [
                        'type'       => $transaction['type'],
                        'expires_at' => $expiresAt,
                    ];
                }
            }
        }

        return $purchasedCourses;
    }
}