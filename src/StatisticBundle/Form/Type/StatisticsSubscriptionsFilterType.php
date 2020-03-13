<?php

namespace StatisticBundle\Form\Type;

use Doctrine\Bundle\MongoDBBundle\Form\Type\DocumentType;
use Doctrine\ODM\MongoDB\DocumentRepository;
use Irev\MainBundle\Document\Product;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class StatisticsSubscriptionsFilterType extends StatisticsFilterType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('product', DocumentType::class, [
            'required' => false,
            'class' => Product::class,
            'choice_label' => 'name',
            'query_builder' => static function (DocumentRepository $repository) {
                return $repository
                    ->createQueryBuilder()
                    ->sort(['position' => 1])
                ;
            }
        ]);

        $builder
            ->add('sortBy', ChoiceType::class, [
                'label' => 'Сортировать по',
                'choices' => [
                    'Total по убыванию' => 'total_desc',
                    'Название по возрастанию' => 'name_asc'
                ],
                'empty_data' => 'total_desc'
            ])
        ;
    }
}
