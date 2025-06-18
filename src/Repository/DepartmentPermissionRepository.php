<?php

namespace App\Repository;

use App\Entity\DepartmentPermission;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<JobJobPermission>
 *
 * @method DepartmentPermission|null find($id, $lockMode = null, $lockVersion = null)
 * @method DepartmentPermission|null findOneBy(array $criteria, array $orderBy = null)
 * @method DepartmentPermission[]    findAll()
 * @method DepartmentPermission[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepartmentPermissionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DepartmentPermission::class);
    }

    public function add(DepartmentPermission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DepartmentPermission $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return DepartmentPermission[] Returns an array of DepartmentPermission objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('dp.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('dp.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?DepartmentPermission
//    {
//        return $this->createQueryBuilder('w')
//            ->andWhere('dp.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
