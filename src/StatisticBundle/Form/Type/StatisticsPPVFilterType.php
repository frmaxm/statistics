<?php

namespace StatisticBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class StatisticsPPVFilterType extends StatisticsFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('mediaId', TextType::class, [
                'required' => false
            ])
        ;
    }
}
