<?php

namespace App\Factory;

use App\Entity\Worker;
use App\Repository\WorkerRepository;
use DateTime;
use Zenstruck\Foundry\RepositoryProxy;
use Zenstruck\Foundry\ModelFactory;
use Zenstruck\Foundry\Proxy;

/**
 * @extends ModelFactory<Worker>
 *
 * @method static Worker|Proxy createOne(array $attributes = [])
 * @method static Worker[]|Proxy[] createMany(int $number, array|callable $attributes = [])
 * @method static Worker[]|Proxy[] createSequence(array|callable $sequence)
 * @method static Worker|Proxy find(object|array|mixed $criteria)
 * @method static Worker|Proxy findOrCreate(array $attributes)
 * @method static Worker|Proxy first(string $sortedField = 'id')
 * @method static Worker|Proxy last(string $sortedField = 'id')
 * @method static Worker|Proxy random(array $attributes = [])
 * @method static Worker|Proxy randomOrCreate(array $attributes = [])
 * @method static Worker[]|Proxy[] all()
 * @method static Worker[]|Proxy[] findBy(array $attributes)
 * @method static Worker[]|Proxy[] randomSet(int $number, array $attributes = [])
 * @method static Worker[]|Proxy[] randomRange(int $min, int $max, array $attributes = [])
 * @method static WorkerRepository|RepositoryProxy repository()
 * @method Worker|Proxy create(array|callable $attributes = [])
 */
final class WorkerFactory extends ModelFactory
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
            'dni' => self::faker()->numberBetween(10_000_000,99_999_999).'H',
            'name' => self::faker()->firstName(),
            'surname1' => self::faker()->lastName(),
            'surname2' => self::faker()->lastName(),
            'startDate' => new \DateTime(sprintf('-%d days', random_int(1, 100))),
            'endDate' => new \DateTime(sprintf('%d days', random_int(1, 100))),
            'expedientNumber' => 'AYT/'.self::faker()->numberBetween(1,200).'/'.self::faker()->numberBetween(2022,2023),
            'createdAt' => new DateTime(),
            'updatedAt' => new DateTime(),
            'status' => self::faker()->numberBetween(1,4),
        ];
    }

    protected function initialize(): self
    {
        // see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
        return $this
            // ->afterInstantiate(function(Worker $worker): void {})
        ;
    }

    protected static function getClass(): string
    {
        return Worker::class;
    }
}
