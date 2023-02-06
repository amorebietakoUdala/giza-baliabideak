<?php

namespace App\DataFixtures;

use App\Factory\ApplicationFactory;
use App\Factory\DepartmentFactory;
use App\Factory\JobFactory;
use App\Factory\UserFactory;
use App\Factory\WorkerFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        UserFactory::createOne([
            'username' => 'ibilbao',
            'email' => 'ibilbao@amorebieta.eus',
            'roles' => ['ROLE_ADMIN']
        ]);  

        UserFactory::createMany(5);
        // $product = new Product();
        // $manager->persist($product);

        ApplicationFactory::createMany(10);
        DepartmentFactory::createMany(5);
        JobFactory::createMany(20, function() {
            return [
                'applications' => ApplicationFactory::randomSet(3),
                'bosses' => UserFactory::randomSet(2),
            ];
        });
        WorkerFactory::createMany (20, function() {
            return [
                'department' => DepartmentFactory::random(),
                'applications' => ApplicationFactory::randomSet(2),
                'job' => JobFactory::random(),
            ];
        });

        $manager->flush();
    }
}
