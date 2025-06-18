<?php

namespace App\Form;

use App\Entity\Job;
use App\Entity\Worker;
use App\Entity\WorkerJob;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WorkerJobType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $readonly = $options['readonly'];
        $locale = $options['locale'];
        $builder
            ->add('code', null,[
                'label' => 'workerJob.code',
                'disabled' => $readonly,
            ])
            ->add('job', EntityType::class, [
                'label' => 'workerJob.job',
                'class' => Job::class,
                'choice_label' => function ($job) use ($locale) {
                    if ('es' === $locale) {
                        return $job->getTitleEs();
                    } else {
                        return $job->getTitleEu();
                    }
                },
                'disabled' => $readonly,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WorkerJob::class,
            'readonly' => false,
            'new' => false,
            'locale' => 'es',
            'roleBossOnly' => false,
        ]);
    }
}
