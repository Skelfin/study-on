<?php

namespace App\DataFixtures;

use App\Entity\Course;
use App\Entity\Lesson;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $coursesData = [
            [
                'code' => '1course',
                'name' => 'Symfony Basics',
                'description' => 'Описание для 1 кода',
                'type' => Course::TYPE_RENT,
                'price' => 99.9,
                'lessons' => [
                    ['name' => 'Introduction to Symfony', 'content' => 'Learning the basics of Symfony.', 'order' => 1],
                    ['name' => 'Routing and Controllers', 'content' => 'Understanding routing and controllers.', 'order' => 2],
                    ['name' => 'Twig Basics', 'content' => 'Introduction to Twig templating engine.', 'order' => 3],
                ],
            ],
            [
                'code' => '2course',
                'name' => 'Php Advanced',
                'description' => 'Описание для 2 кода',
                'type' => Course::TYPE_FREE,
                'price' => 0,
                'lessons' => [
                    ['name' => 'Namespaces and Autoloading', 'content' => 'How namespaces and autoloading work in PHP.', 'order' => 1],
                    ['name' => 'OOP Concepts', 'content' => 'Advanced object-oriented programming concepts.', 'order' => 2],
                    ['name' => 'Design Patterns', 'content' => 'Common design patterns in PHP.', 'order' => 3],
                ],
            ],
            [
                'code' => '3course',
                'name' => 'Doctrine Essentials',
                'description' => 'Описание для 3 кода',
                'type' => Course::TYPE_BUY,
                'price' => 159,
                'lessons' => [
                    ['name' => 'Doctrine Setup', 'content' => 'Setting up Doctrine ORM in your project.', 'order' => 1],
                    ['name' => 'Entity Mapping', 'content' => 'How to map entities and relationships.', 'order' => 2],
                    ['name' => 'Doctrine Queries', 'content' => 'Writing DQL and using the QueryBuilder.', 'order' => 3],
                ],
            ],
        ];

        foreach ($coursesData as $courseData) {
            $course = new Course();
            $course->setCode($courseData['code']);
            $course->setName($courseData['name']);
            $course->setDescription($courseData['description']);
            $course->setType($courseData['type']);
            $course->setPrice($courseData['price']);

            foreach ($courseData['lessons'] as $lessonData) {
                $lesson = new Lesson();
                $lesson->setName($lessonData['name']);
                $lesson->setContent($lessonData['content']);
                $lesson->setLessonOrder($lessonData['order']);
                $lesson->setCourse($course);

                $manager->persist($lesson);
            }

            $manager->persist($course);
        }

        $manager->flush();
    }
}