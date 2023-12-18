<?php

namespace App\Repository;

use App\Entity\Application;
use App\Entity\Department;
use App\Entity\Job;
use App\Entity\Worker;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Worker>
 *
 * @method Worker|null find($id, $lockMode = null, $lockVersion = null)
 * @method Worker|null findOneBy(array $criteria, array $orderBy = null)
 * @method Worker[]    findAll()
 * @method Worker[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WorkerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Worker::class);
    }

    public function add(Worker $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Worker $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

   /**
    * @return Worker[] Returns an array of Worker objects
    */
    public function findByJob(Job $job): array
    {
        $qb = $this->createQueryBuilder('w')
            ->andWhere('w.job = :job')
            ->setParameter('job', $job)
            ->orderBy('w.id', 'DESC');
        return $qb->getQuery()->getResult();
    }
 
   /**
    * @return Worker[] Returns an array of Worker objects
    */
    public function findByApplication(Application $application): array
    {
        $qb = $this->createQueryBuilder('w')
            ->andWhere('w.application = :application')
            ->setParameter('application', $application)
            ->orderBy('w.id', 'DESC');
        return $qb->getQuery()->getResult();
    }

    /**
    * @return Worker[] Returns an array of Worker objects
    */
    public function findByDepartment(Department $department): array
    {
        $qb = $this->createQueryBuilder('w')
            ->andWhere('w.department = :department')
            ->setParameter('department', $department)
            ->orderBy('w.id', 'DESC');
        return $qb->getQuery()->getResult();
    }

    /**
    * @return Worker[] Returns an array of Worker objects
    */
   public function findByExample(array $criteria): array
   {
        //dump($criteria);
        $criteria = $this->remove_blank_filters($criteria);
        //dd($criteria);
        $qb = $this->createQueryBuilder('w');
        foreach ( $criteria as $field => $value ) {
            $qb->andWhere('w.'.$field.' = :'.$field)
                ->setParameter($field, $value);
        }
        //dd($qb->orderBy('w.id', 'DESC')->getQuery(),$qb->orderBy('w.id', 'DESC')->getQuery()->getResult());
        return $qb->orderBy('w.id', 'DESC')
           ->getQuery()
           ->getResult();
   }

    private function remove_blank_filters($criteria)
    {
        $new_criteria = [];
        foreach ($criteria as $key => $value) {
            if (!empty($value)) {
                $new_criteria[$key] = $value;
            }
        }

        return $new_criteria;
    }

    public function findEndsInNextDays(int $days): ?array
    {
        $startDate = new DateTime();
        $endDate = (new DateTime())->modify("+$days days");
        return $this->createQueryBuilder('w')
            ->andWhere('w.endDate > :startDate')
            ->setParameter('startDate', $startDate)
            ->andWhere('w.endDate <= :endDate')
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findExpired(): ?array
    {
        $today = new DateTime();
        return $this->createQueryBuilder('w')
            ->andWhere('w.endDate < :today')
            ->setParameter('today', $today)
            ->andWhere('w.status != :deleted') 
            ->setParameter('deleted', Worker::STATUS_DELETED)
            ->getQuery()
            ->getResult()
        ;
    }
}
