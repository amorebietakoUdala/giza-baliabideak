<?php

namespace App\Factory;

use App\Entity\Department;
use App\Repository\DepartmentRepository;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Department>
 *
 * @method static Department|Proxy createOne(array $attributes = [])
 * @method static Department[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Department[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Department|Proxy find(object|array|mixed $criteria)
 * @method static Department|Proxy findOrCreate(array $attributes)
 * @method static Department|Proxy first(string $sortedField = 'id')
 * @method static Department|Proxy last(string $sortedField = 'id')
 * @method static Department|Proxy random(array $attributes = [])
 * @method static Department|Proxy randomOrCreate(array $attributes = [])
 * @method static Department[]|Proxy[] all()
 * @method static Department[]|Proxy[] findBy(array $attributes)
 * @method static Department[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Department[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static DepartmentRepository|RepositoryProxy repository()
 * @method Department|Proxy create(array|callable $attributes = [])
 */
final class DepartmentFactory extends ModelFactory
{
    public function __construct()
    {
        parent::__construct();

        // TODO inject services if required (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services)
    }

    protected function getDefaults(): array
    {
        return [
            // TODO add your default values here (https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories)
            'name' => self::faker()->words(4, true),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Department $department): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Department::class;
    }
}
