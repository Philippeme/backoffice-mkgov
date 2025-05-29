<?php 

namespace App\Form;

use App\Entity\PublicEntity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PublicEntityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Entity Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter entity name'
                ]
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Entity Type',
                'choices' => [
                    'Ministry' => 'ministry',
                    'Agency' => 'agency',
                    'Department' => 'department',
                    'Directorate' => 'directorate',
                    'Prefecture' => 'prefecture',
                    'Sub-Prefecture' => 'sub_prefecture',
                    'Municipality' => 'municipality',
                    'Council' => 'council',
                    'Embassy' => 'embassy',
                    'Consulate' => 'consulate'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'placeholder' => 'Enter entity description'
                ]
            ])
            ->add('address', TextareaType::class, [
                'label' => 'Address',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 2,
                    'placeholder' => 'Enter complete address'
                ]
            ])
            ->add('phoneNumber', TextType::class, [
                'label' => 'Phone Number',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter phone number'
                ]
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email Address',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter email address'
                ]
            ])
            ->add('website', TextType::class, [
                'label' => 'Website URL',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter website URL'
                ]
            ])
            ->add('region', ChoiceType::class, [
                'label' => 'Region',
                'choices' => [
                    'Centre' => 'centre',
                    'Littoral' => 'littoral',
                    'Ouest' => 'ouest',
                    'Nord' => 'nord',
                    'Adamaoua' => 'adamaoua',
                    'Est' => 'est',
                    'Nord-Ouest' => 'nord-ouest',
                    'Sud-Ouest' => 'sud-ouest',
                    'Sud' => 'sud',
                    'Extrême-Nord' => 'extreme-nord'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active Entity',
                'required' => false,
                'attr' => ['class' => 'form-check-input']
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Save Entity',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PublicEntity::class,
        ]);
    }
}