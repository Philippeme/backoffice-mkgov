<?php 

namespace App\Form;

use App\Entity\Document;
use App\Entity\Request;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Document Name',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Enter document name'
                ]
            ])
            ->add('documentType', ChoiceType::class, [
                'label' => 'Document Type',
                'choices' => [
                    'Identity Document' => 'identity',
                    'Birth Certificate' => 'birth_certificate',
                    'Marriage Certificate' => 'marriage_certificate',
                    'Death Certificate' => 'death_certificate',
                    'Passport' => 'passport',
                    'Driving License' => 'driving_license',
                    'Academic Certificate' => 'academic_certificate',
                    'Business Document' => 'business_document',
                    'Legal Document' => 'legal_document',
                    'Medical Document' => 'medical_document',
                    'Other' => 'other'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Pending' => 'pending',
                    'Verified' => 'verified',
                    'Rejected' => 'rejected',
                    'Expired' => 'expired'
                ],
                'attr' => ['class' => 'form-select']
            ])
            ->add('request', EntityType::class, [
                'class' => Request::class,
                'choice_label' => function (Request $request) {
                    return $request->getTitle() . ' (#' . $request->getTrackingNumber() . ')';
                },
                'label' => 'Associated Request',
                'required' => false,
                'attr' => ['class' => 'form-select'],
                'placeholder' => 'Select a request (optional)'
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 3,
                    'placeholder' => 'Enter any additional notes'
                ]
            ]);

        if (!$options['is_edit']) {
            $builder->add('file', FileType::class, [
                'label' => 'Document File',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
                        ],
                        'mimeTypesMessage' => 'Please upload a valid document file'
                    ])
                ],
                'attr' => [
                    'class' => 'form-control',
                    'accept' => '.pdf,.jpg,.jpeg,.png,.gif,.doc,.docx'
                ]
            ]);
        }

        $builder->add('submit', SubmitType::class, [
            'label' => $options['is_edit'] ? 'Update Document' : 'Upload Document',
            'attr' => ['class' => 'btn btn-primary']
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Document::class,
            'is_edit' => false,
        ]);
    }
}