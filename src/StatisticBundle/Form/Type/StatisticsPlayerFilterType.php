<?php

namespace StatisticBundle\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Irev\MainBundle\Document\Channel;
use Irev\MainBundle\Form\Type\DateRangeType;
use StatisticBundle\Filter\StatisticsFilter;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class StatisticsPlayerFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('dateRange', DateRangeType::class, [
                'required' => false,
                'label' => 'Даты'
            ])
            ->add('channel', DocumentType::class, [
                'required' => false,
                'label' => 'Канал',
                'choice_label' => 'title',
                'class' => Channel::class
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(['data_class' => StatisticsFilter::class]);
    }

}
