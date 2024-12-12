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
                'query_builder' => fn(UserRepository $er) => $er->findBossesQB(),                
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
