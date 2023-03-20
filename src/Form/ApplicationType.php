<?php

namespace App\Form;

use App\Entity\Application;
use App\Entity\Role;
use App\Entity\SubApplication;
use App\Repository\SubApplicationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
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
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Application::class,
            'readonly' => false,
            'new' => true,
            'locale' => 'es',
        ]);
    }
}
