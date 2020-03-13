<?php

namespace StatisticBundle\Form\Type;

use Irev\MainBundle\Form\Type\DateRangeType;
use StatisticBundle\Filter\StatisticsFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatisticsFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateRange', DateRangeType::class, [
                'label' => 'Период',
                'required' => false
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => StatisticsFilter::class]);
    }

}
