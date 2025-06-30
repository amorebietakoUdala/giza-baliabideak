<?php

namespace App\Form;

use App\Entity\Application;
use App\Entity\JobPermission;
use App\Entity\Role;
use App\Entity\SubApplication;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class JobPermissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $locale = $options['locale'];
        $builder
        ->add('application',EntityType::class, [
            'label' => 'workerApplication.application',
            'class' => Application::class,
            'required' => true,
            'disabled' => $readonly,
        ])
        ->add('subApplication',EntityType::class, [
            'placeholder' => 'placeholder.choose',
            'label' => 'workerApplication.subApplication',
            'class' => SubApplication::class,
            'required' => false,
            'disabled' => $readonly,
            'data' => null,
        ])
        ->add('roles',EntityType::class, [
            'label' => 'workerApplication.roles',
            'class' => Role::class,
            'multiple' => true,
            'expanded' => false,
            'required' => true,
            'disabled' => $readonly,
            'constraints' => [
                new NotNull(),
            ]
        ])
        ->add('notes', TextareaType::class, [
            'label' => 'workerApplication.notes',
            'required' => false,
            'disabled' => $readonly,
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => JobPermission::class,
            'readonly' => false,
            'locale' => 'es',
        ]);
    }
}
