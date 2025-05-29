<?php

namespace App\Form;

use App\Entity\Request;
use App\Entity\User;
use App\Entity\Procedure;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Title',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter request title'
                ]
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Enter detailed description'
                ]
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Processing' => 'processing',
                    'Under Review' => 'under_review',
                    'Approved' => 'approved',
                    'Rejected' => 'rejected',
                    'Completed' => 'completed'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('serviceFamily', ChoiceType::class, [
                'label' => 'Service Family',
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
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => function (User $user) {
                    return $user->getFullName() . ' (' . $user->getEmail() . ')';
                },
                'label' => 'User',
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Select a user'
            ])
            ->add('procedure', EntityType::class, [
                'class' => Procedure::class,
                'choice_label' => 'name',
                'label' => 'Associated Procedure',
                'required' => false,
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Select a procedure (optional)'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save Request',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Request::class,
        ]);
    }
}