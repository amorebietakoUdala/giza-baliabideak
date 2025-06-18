<?php

namespace App\Form;

use App\Entity\Application;
use App\Entity\Role;
use App\Entity\SubApplication;
use App\Entity\User;
use App\Repository\SubApplicationRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $locale = $options['locale'];
        $application = $options['data'];
        $isAdmin = $options['isAdmin'];
        $builder
            ->add('id', HiddenType::class)
            ->add('name', null, [
                'label' => 'application.name',
                'disabled' => $readonly,
            ])
            ->add('appOwnersEmails', null, [
                'label' => 'application.appOwnersEmails',
                'disabled' => $readonly,
            ])
            ->add('userCreatorEmail', null, [
                'label' => 'application.userCreatorEmail',
                'disabled' => $readonly,
            ])
            ->add('subApplications', EntityType::class, [
                'query_builder' => function (SubApplicationRepository $er) use ($application) {
                    if ($application->getId() !== null) {
                        return $er->findByApplicationQB($application->getId());
                    }
                    return $er->findAllQB();
                },
                'label' => 'application.subApplications',
                'disabled' => $readonly,
                'class' => SubApplication::class,
                'disabled' => $readonly,
                'multiple' => true,
                'required' => false,
                'expanded' => true,

            ])
            ->add('roles', EntityType::class, [
                'class' => Role::class,
                'label' => 'application.roles',
                'disabled' => $readonly,
                'multiple' => true,
                'required' => false,
                'expanded' => true,
            ])
            ->add('appOwners', EntityType::class, [
                'class' => User::class,
                'label' => 'application.appOwners',
                'disabled' => $readonly,
                'multiple' => true,
                'required' => false,
                'expanded' => true,
                'query_builder' => function (UserRepository $er) {
                    return $er->findByRolQB("ROLE_APP_OWNER");
                },
            ])
            ->add('general', CheckboxType::class, [
                'label' => 'application.general',
                'disabled' => $readonly,
            ]);
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
            'readonly' => false,
            'new' => true,
            'locale' => 'es',
            'isAdmin' => false,
        ]);
    }
}
