<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\SubApplication;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SubApplication>
 *
 * @method SubApplication|null find($id, $lockMode = null, $lockVersion = null)
 * @method SubApplication|null findOneBy(array $criteria, array $orderBy = null)
 * @method SubApplication[]    findAll()
 * @method SubApplication[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubApplicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SubApplication::class);
    }

    public function add(SubApplication $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SubApplication $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByApplicationQB($applicationId)
    {
        return $this->createQueryBuilder('s')
        ->andWhere('s.application = :application')
        ->setParameter('application', $applicationId)        
        ->addOrderBy('s.nameEs', 'ASC');
    }

    public function findAllQB()
    {
        return $this->createQueryBuilder('s')->addOrderBy('s.nameEs', 'ASC');
    }    

//    /**
//     * @return SubApplication[] Returns an array of SubApplication objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?SubApplication
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
