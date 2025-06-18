<?php

namespace App\Form;


use App\Entity\Department;

use App\Entity\Worker;
use App\Validator\IsValidDNI;
use App\Validator\IsValidExpedientNumber;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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
        $isAppOwnerOnly = $options['isAppOwnerOnly'];
        $builder
            ->add('dni', TextType::class, [
                'label' => 'worker.dni',
                'constraints' => [
                    new NotBlank(),
                    new IsValidDNI(),
                ],
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
            ])
            ->add('name',null,[
                'label' => 'worker.name',
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('surname1',null,[
                'label' => 'worker.surname1',
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
                'constraints' => [
                    new NotBlank(),
                ],
            ])
            ->add('surname2',null,[
                'label' => 'worker.surname2',
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
            ])
            ->add('workerJob',WorkerJobType::class,[
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
                'locale' => $locale,
            ])
            ->add('startDate', DateTimeType::class, [
                'label' => 'worker.startDate',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly ,
            ])
            ->add('endDate', DateTimeType::class, [
                'label' => 'worker.endDate',
                'widget' => 'single_text',
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
            ])
            ->add('noEndDate', CheckboxType::class, [
                'label' => 'worker.noEndDate',
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
            ])
            ->add('expedientNumber',null,[
                'label' => 'worker.expedientNumber',
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
                'constraints' => [
                    new NotBlank(),
                    new IsValidExpedientNumber(),
                ],
            ])
            ->add('department', EntityType::class, [
                'label' => 'worker.department',
                'class' => Department::class,
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
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
            ])->add('username',null,[
                'label' => 'worker.username',
                'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
                'required' => false,
            ])
            ;
            if (!$new) {
                $builder->add('status',ChoiceType::class,[
                    'label' => 'worker.status',
                    'disabled' => $readonly || $roleBossOnly || $isAppOwnerOnly,
                    'choices' => [
                        'status.'.Worker::STATUS_USERNAME_PENDING => Worker::STATUS_USERNAME_PENDING,
                        'status.'.Worker::STATUS_REVISION_PENDING => Worker::STATUS_REVISION_PENDING,
                        'status.'.Worker::STATUS_APPROVAL_PENDING => Worker::STATUS_APPROVAL_PENDING,
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
            'isAppOwnerOnly' => false,
        ]);
    }
}
