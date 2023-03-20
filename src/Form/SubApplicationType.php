<?php

namespace App\Form;

use App\Entity\Application;
use App\Entity\SubApplication;
use App\Repository\ApplicationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubApplicationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $locale = $options['locale'];
        $builder
        ->add('id', HiddenType::class)
        ->add('nameEs', null, [
            'label' => 'subApplication.nameEs',
            'disabled' => $readonly,
        ])
        ->add('nameEu', null, [
            'label' => 'subApplication.nameEu',
            'disabled' => $readonly,
        ])
        ->add('application',EntityType::class, [
            'label' => 'subApplication.application',
            'class' => Application::class,
            'required' => true,
            'disabled' => $readonly,
        ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubApplication::class,
            'readonly' => false,
            'locale' => 'es',
        ]);
    }
}
