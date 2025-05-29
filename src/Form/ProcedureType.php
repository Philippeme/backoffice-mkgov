<?php

namespace App\Form;

use App\Entity\Procedure;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProcedureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Procedure Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter procedure name'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Enter procedure description'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Draft' => 'draft',
                    'Active' => 'active',
                    'Inactive' => 'inactive',
                    'Archived' => 'archived'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Category',
                'choices' => [
                    'Police & Justice' => 'police_justice',
                    'Family' => 'family',
                    'Transport' => 'transport',
                    'Education' => 'education',
                    'Business' => 'business',
                    'Public Service' => 'public_service',
                    'Land & Construction' => 'land_construction',
                    'Consular Services' => 'consular',
                    'Health' => 'health',
                    'Civic Life' => 'civic_life'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('estimatedDuration', NumberType::class, [
                'label' => 'Estimated Duration (days)',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter estimated duration in days',
                    'min' => 0
                ]
            ])
            ->add('cost', MoneyType::class, [
                'label' => 'Cost',
                'currency' => 'XAF',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter cost amount'
                ]
            ])
            ->add('currency', ChoiceType::class, [
                'label' => 'Currency',
                'choices' => [
                    'Central African CFA Franc (XAF)' => 'XAF',
                    'US Dollar (USD)' => 'USD',
                    'Euro (EUR)' => 'EUR'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save Procedure',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Procedure::class,
        ]);
    }
}