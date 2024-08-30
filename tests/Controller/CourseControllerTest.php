<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\Tests\AbstractTest;
use App\Entity\Course;

class CourseControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    private function getFirstCourseId(): ?int
    {
        $entityManager = self::getEntityManager();
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);
        return $course ? $course->getId() : null;
    }

    // GET запросы

    public function testCourseIndex(): void
    {
        $client = self::getClient();
        $crawler = $client->request('GET', '/courses/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $courseCount = $crawler->filter('.col-md-4')->count();
        $this->assertEquals(3, $courseCount);
    }

    public function testCourseShow(): void
    {
        $courseId = $this->getFirstCourseId();
        if ($courseId === null) {
            $this->fail('Course not found in the database.');
        }

        $client = self::getClient();
        $crawler = $client->request('GET', "/courses/{$courseId}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $lessonCount = $crawler->filter('ol > li')->count();
        $this->assertEquals(3, $lessonCount);
    }

    public function testCourseShowNotFound(): void
    {
        $client = self::getClient();
        $client->request('GET', '/courses/999999');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }


    public function testCourseEdit(): void
    {
        $courseId = $this->getFirstCourseId();
        if ($courseId === null) {
            $this->fail('Course not found in the database.');
        }

        $client = self::getClient();
        $client->request('GET', "/courses/{$courseId}/edit");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    //POST запросы

    public function testCourseCreate(): void
    {
        $client = self::getClient();

        $crawler = $client->request('GET', '/courses/');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $link = $crawler->selectLink('Добавить новый курс')->link();
        $crawler = $client->click($link);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Сохранить')->form([
            'course[name]' => 'Test Course',
            'course[description]' => 'This is a test course.',
        ]);

        $client->submit($form);
        $this->assertContains($client->getResponse()->getStatusCode(), [302, 303]);
        $crawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString('Test Course', $client->getResponse()->getContent());
    }

    public function testCourseEditPost(): void
    {
        $courseId = $this->getFirstCourseId();
        if ($courseId === null) {
            $this->fail('Course not found in the database.');
        }

        $client = self::getClient();

        $crawler = $client->request('GET', "/courses/{$courseId}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Сохранить')->form([
            'course[name]' => 'Updated Course',
            'course[description]' => 'This is an updated description.',
        ]);

        $client->submit($form);
        $this->assertContains($client->getResponse()->getStatusCode(), [302, 303]);
        $crawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString('Updated Course', $client->getResponse()->getContent());
    }

    public function testCourseDelete(): void
    {
        $courseId = $this->getFirstCourseId();
        if ($courseId === null) {
            $this->fail('Course not found in the database.');
        }

        $client = self::getClient();

        $crawler = $client->request('GET', "/courses/{$courseId}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Удалить')->form();
        $client->submit($form);

        $this->assertContains($client->getResponse()->getStatusCode(), [302, 303]);
        $crawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertStringNotContainsString('course_' . $courseId, $client->getResponse()->getContent());
    }

    public function testCourseCreateInvalidData(): void
    {
        $client = self::getClient();

        $crawler = $client->request('GET', '/courses/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Сохранить')->form([
            'course[name]' => '',
            'course[description]' => 'This is a test course.',
        ], 'POST', ['novalidate' => 'novalidate']);

        $crawler = $client->submit($form);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString('Название не должно быть пустым', $client->getResponse()->getContent());
    }

    public function testCourseEditInvalidData(): void
    {
        $courseId = $this->getFirstCourseId();
        if ($courseId === null) {
            $this->fail('Course not found in the database.');
        }

        $client = self::getClient();
        $crawler = $client->request('GET', "/courses/{$courseId}/edit");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Сохранить')->form([
            'course[name]' => '',
            'course[description]' => 'This is an updated description.',
        ], 'POST', ['novalidate' => 'novalidate']);

        $crawler = $client->submit($form);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString('Название не должно быть пустым', $client->getResponse()->getContent());
    }
}