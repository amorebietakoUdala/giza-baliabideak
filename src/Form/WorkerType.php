<?php

namespace App\Form;

use App\Entity\Application;
use App\Entity\Department;
use App\Entity\Job;
use App\Entity\Worker;
use App\Entity\Permission;
use App\Entity\WorkerJob;
use App\Repository\ApplicationRepository;
use App\Validator\IsValidDNI;
use App\Validator\IsValidExpedientNumber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class WorkerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $roleBossOnly = $options['roleBossOnly'];
        $locale = $options['locale'];
        $new = $options['new'];
        $builder
            ->add('dni', TextType::class, [
                'label' => 'worker.dni',
                'constraints' => [
                    new NotBlank(),
                    new IsValidDNI(),
                ],
                'disabled' => $readonly || $roleBossOnly,
            ])
            ->add('name',null,[
                'label' => 'worker.name',
                'disabled' => $readonly || $roleBossOnly,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('surname1',null,[
                'label' => 'worker.surname1',
                'disabled' => $readonly || $roleBossOnly,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('surname2',null,[
                'label' => 'worker.surname2',
                'disabled' => $readonly || $roleBossOnly,
            ])
            ->add('workerJob',WorkerJobType::class,[
                'disabled' => $readonly || $roleBossOnly,
                'locale' => $locale,
                // 'choice_label' => function ($workerJob) use ($locale) {
                //     if ('es' === $locale) {
                //         return '('.$workerJob->getCode().') '.$workerJob->getJob()->getTitleEs();
                //     } else {
                //         return '('.$workerJob->getCode().') '.$workerJob->getJob()->getTitleEu();
                //     }
                // },
                // 'constraints' => [
                //     new NotBlank(),
                // ],
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'worker.startDate',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'disabled' => $readonly || $roleBossOnly,
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'worker.endDate',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'disabled' => $readonly || $roleBossOnly,
            ])
            ->add('noEndDate', CheckboxType::class, [
                'label' => 'worker.noEndDate',
                'disabled' => $readonly || $roleBossOnly,
            ])
            ->add('expedientNumber',null,[
                'label' => 'worker.expedientNumber',
                'disabled' => $readonly || $roleBossOnly,
                'constraints' => [
                    new NotBlank(),
                    new IsValidExpedientNumber(),
                ],
            ])
            ->add('department', EntityType::class, [
                'label' => 'worker.department',
                'class' => Department::class,
                'disabled' => $readonly || $roleBossOnly,
                'placeholder' => 'worker.department.placeholder',
                'choice_label' => function ($department) use ($locale) {
                    if ('es' === $locale) {
                        return $department->getNameEs();
                    } else {
                        return $department->getNameEu();
                    }
                },
                'constraints' => [
                    new NotBlank(),
                ]
            ])
            ;
            if (!$new) {
                $builder->add('status',ChoiceType::class,[
                    'label' => 'worker.status',
                    'disabled' => $readonly || $roleBossOnly,
                    'choices' => [
                        'status.'.Worker::STATUS_RRHH_NEW => Worker::STATUS_RRHH_NEW,
                        'status.'.Worker::STATUS_REVISION_PENDING => Worker::STATUS_REVISION_PENDING,
                        'status.'.Worker::STATUS_IN_PROGRESS => Worker::STATUS_IN_PROGRESS,
                        'status.'.Worker::STATUS_REGISTERED => Worker::STATUS_REGISTERED,
                        'status.'.Worker::STATUS_DELETED => Worker::STATUS_DELETED,
                        ],
                ]);
            }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Worker::class,
            'readonly' => false,
            'new' => false,
            'locale' => 'es',
            'roleBossOnly' => false,
        ]);
    }
}
