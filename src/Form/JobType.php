<?php

namespace App\Form;

use App\Entity\Application;
use App\Entity\Job;
use App\Entity\User;
use App\Repository\ApplicationRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $builder
            ->add('code', null, [
                'label' => 'job.code',
                'disabled' => $readonly,
            ])
            ->add('titleEs', null, [
                'label' => 'job.titleEs',
                'disabled' => $readonly,
            ])
            ->add('titleEu', null, [
                'label' => 'job.titleEu',
                'disabled' => $readonly,
            ])
            ->add('bosses', EntityType::class, [
                'class' => User::class,
                'label' => 'job.bosses',
                'multiple' => true,
                'disabled' => $readonly,
                'query_builder' => function(UserRepository $er) {
                    return $er->findBossesQB();
                },                
            ])
            ->add('applications', EntityType::class, [
                'class' => Application::class,
                'label' => 'worker.applications',
                'query_builder' => function (ApplicationRepository $er) {
                    return $er->findAllQB();
                },
                'multiple' => true,
                'expanded' => true,
                'disabled' => $readonly,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Job::class,
            'readonly' => false,
        ]);
    }
}
