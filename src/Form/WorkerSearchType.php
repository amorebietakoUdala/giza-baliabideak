<?php

namespace App\Form;

use App\Entity\Worker;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkerSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('dni', null, [
                'label' => 'worker.dni',
            ])
            ->add('name', null, [
                'label' => 'worker.name',
            ])
            ->add('surname1', null, [
                'label' => 'worker.surname1',
            ])
            ->add('expedientNumber', null, [
                'label' => 'worker.expedientNumber',
            ])
            ->add('status',ChoiceType::class,[
                'label' => 'worker.status',
                'placeholder' => 'worker.status.placeholder',
                'choices' => [
                    'status.'.Worker::STATUS_USERNAME_PENDING => Worker::STATUS_USERNAME_PENDING,
                    'status.'.Worker::STATUS_REVISION_PENDING => Worker::STATUS_REVISION_PENDING,
                    'status.'.Worker::STATUS_APPROVAL_PENDING => Worker::STATUS_APPROVAL_PENDING,
                    'status.'.Worker::STATUS_IN_PROGRESS => Worker::STATUS_IN_PROGRESS,
                    'status.'.Worker::STATUS_REGISTERED => Worker::STATUS_REGISTERED,
                    'status.'.Worker::STATUS_DELETED => Worker::STATUS_DELETED,
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
