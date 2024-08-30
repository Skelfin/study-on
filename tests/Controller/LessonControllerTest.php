<?php

namespace App\Tests\Controller;

use App\DataFixtures\CourseFixtures;
use App\Tests\AbstractTest;
use App\Entity\Lesson;
use App\Entity\Course;

class LessonControllerTest extends AbstractTest
{
    protected function getFixtures(): array
    {
        return [CourseFixtures::class];
    }

    private function getFirstLessonId(): ?int
    {
        $entityManager = self::getEntityManager();
        $lesson = $entityManager->getRepository(Lesson::class)->findOneBy([]);
        return $lesson ? $lesson->getId() : null;
    }

    private function getFirstCourseId(): ?int
    {
        $entityManager = self::getEntityManager();
        $course = $entityManager->getRepository(Course::class)->findOneBy([]);
        return $course ? $course->getId() : null;
    }

    // GET запросы

    public function testLessonShow(): void
    {
        $lessonId = $this->getFirstLessonId();
        if ($lessonId === null) {
            $this->fail('Lesson not found in the database.');
        }

        $client = self::getClient();
        $crawler = $client->request('GET', "/lessons/{$lessonId}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $lessonContent = $crawler->filter('.container')->text();
        $this->assertNotEmpty($lessonContent);
    }

    public function testLessonShowNotFound(): void
    {
        $client = self::getClient();
        $client->request('GET', '/lessons/999999');
        $this->assertEquals(404, $client->getResponse()->getStatusCode());
    }

    // Post запросы

    public function testLessonCreate(): void
    {
        $client = self::getClient();
        $courseId = $this->getFirstCourseId();

        $crawler = $client->request('GET', "/courses/{$courseId}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'Test Lesson',
            'lesson[content]' => 'This is a test lesson.',
            'lesson[position]' => 4,
        ]);

        $client->submit($form);
        $this->assertContains($client->getResponse()->getStatusCode(), [302, 303]);
        $crawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString('Test Lesson', $client->getResponse()->getContent());
    }

    public function testLessonEditPost(): void
    {
        $lessonId = $this->getFirstLessonId();
        if ($lessonId === null) {
            $this->fail('Lesson not found in the database.');
        }

        $client = self::getClient();

        $crawler = $client->request('GET', "/lessons/{$lessonId}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => 'Updated Lesson',
            'lesson[content]' => 'This is an updated lesson.',
            'lesson[position]' => 1,
        ]);

        $client->submit($form);
        $this->assertContains($client->getResponse()->getStatusCode(), [302, 303]);
        $crawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString('Updated Lesson', $client->getResponse()->getContent());
    }

    public function testLessonDelete(): void
    {
        $lessonId = $this->getFirstLessonId();
        if ($lessonId === null) {
            $this->fail('Lesson not found in the database.');
        }

        $client = self::getClient();

        $crawler = $client->request('GET', "/lessons/{$lessonId}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Удалить')->form();
        $client->submit($form);

        $this->assertContains($client->getResponse()->getStatusCode(), [302, 303]);
        $crawler = $client->followRedirect();
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $this->assertStringNotContainsString('lesson_' . $lessonId, $client->getResponse()->getContent());
    }

    public function testLessonCreateInvalidData(): void
    {
        $client = self::getClient();
        $courseId = $this->getFirstCourseId();

        $crawler = $client->request('GET', "/courses/{$courseId}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $link = $crawler->selectLink('Добавить урок')->link();
        $crawler = $client->click($link);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => '',
            'lesson[content]' => '',
            'lesson[position]' => 4,
        ], 'POST', ['novalidate' => 'novalidate']);

        $crawler = $client->submit($form);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString('Название не должно быть пустым.', $client->getResponse()->getContent());
        $this->assertStringContainsString('Содержимое не должно быть пустым.', $client->getResponse()->getContent());
    }


    public function testLessonEditInvalidData(): void
    {
        $lessonId = $this->getFirstLessonId();
        if ($lessonId === null) {
            $this->fail('Lesson not found in the database.');
        }

        $client = self::getClient();

        $crawler = $client->request('GET', "/lessons/{$lessonId}");
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $link = $crawler->selectLink('Редактировать')->link();
        $crawler = $client->click($link);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        $form = $crawler->selectButton('Сохранить')->form([
            'lesson[name]' => '',
            'lesson[content]' => '',
            'lesson[position]' => 1,
        ], 'POST', ['novalidate' => 'novalidate']);

        $crawler = $client->submit($form);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());

        $this->assertStringContainsString('Название не должно быть пустым.', $client->getResponse()->getContent());
        $this->assertStringContainsString('Содержимое не должно быть пустым.', $client->getResponse()->getContent());
    }
}