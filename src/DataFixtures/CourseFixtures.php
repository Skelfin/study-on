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
                'code' => 'symfony_basics',
                'name' => 'Symfony Basics',
                'description' => 'Описание для 1 кода',
                'lessons' => [
                    ['name' => 'Introduction to Symfony', 'content' => 'Learning the basics of Symfony.', 'order' => 1],
                    ['name' => 'Routing and Controllers', 'content' => 'Understanding routing and controllers.', 'order' => 2],
                    ['name' => 'Twig Basics', 'content' => 'Introduction to Twig templating engine.', 'order' => 3],
                ],
            ],
            [
                'code' => 'php_advanced',
                'name' => 'Advanced PHP',
                'description' => 'Описание для 2 кода',
                'lessons' => [
                    ['name' => 'Namespaces and Autoloading', 'content' => 'How namespaces and autoloading work in PHP.', 'order' => 1],
                    ['name' => 'OOP Concepts', 'content' => 'Advanced object-oriented programming concepts.', 'order' => 2],
                    ['name' => 'Design Patterns', 'content' => 'Common design patterns in PHP.', 'order' => 3],
                ],
            ],
            [
                'code' => 'doctrine_essentials',
                'name' => 'Doctrine Essentials',
                'description' => 'Описание для 3 кода',
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

            foreach ($courseData['lessons'] as $lessonData) {
                $lesson = new Lesson();
                $lesson->setName($lessonData['name']);
                $lesson->setContent($lessonData['content']);
                $lesson->setPosition($lessonData['order']);
                $lesson->setCourse($course);

                $manager->persist($lesson);
            }

            $manager->persist($course);
        }

        $manager->flush();
    }
}