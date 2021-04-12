<?php

declare(strict_types=1);

namespace App\Form\Type\Base;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BaseUserType
 *
 * @codeCoverageIgnore
 *
 * Manage user actions form data.
 */
class BaseUserType extends AbstractType
{
    /**
     * @var DataTransformerInterface
     */
    private DataTransformerInterface $dataTransformer;

    /**
     * BaseUserType constructor.
     *
     * @param DataTransformerInterface $dataTransformer
     */
    public function __construct(DataTransformerInterface $dataTransformer)
    {
        $this->dataTransformer = $dataTransformer;
    }

    /**
     * Build the form with custom fields.
     *
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @return void
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label'      => "Nom d'utilisateur",
                'empty_data' => ''
            ])
            ->add('email', EmailType::class, [
                'label'      => 'Adresse email',
                'empty_data' => ''
            ])
            ->add('roles', ChoiceType::class, [
                'label'          => 'Rôle utilisateur (fonction)',
                'choices'        => [
                    'Gestionnaire de tâche' => User::ROLES['user'],
                    'Administrateur'        => User::ROLES['admin']
                ],
                'invalid_message' => 'Inutile d\'altérer les données autorisées !'
                // No "empty_data => ''" option is used here since "ROLE_USER" is set by default (also in constructor)!
            ])
            ->add('password', RepeatedType::class, [
                'type'            => PasswordType::class,
                'options'         => [
                    // Keep previous filled in value with repeated type options array
                    'always_empty' => false,
                    // Use closure to set password "null" value as empty string
                    'empty_data' => function (FormInterface $form): FormInterface {
                        if (null === $form->getData()) {
                            $form->setData('');
                        }

                        return $form;
                    }
                ],
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'first_options'   => [
                    'label'     => 'Mot de passe',
                    'help'      => '8 à 20 caractères sans espace, avec au moins 1 majuscule, 1 minuscule, 
                                    1 chiffre, 1 caractère spécial (exemple : Azerty1$)',
                    'help_attr' => ['class' => 'ts'] // small typography
                ],
                'second_options'  => [
                    'label' => 'Tapez le mot de passe à nouveau'
                ]
            ]);
        // Transform roles data to get an array of string
        $builder
            ->get('roles')
            // Add custom data transformer
            ->addModelTransformer($this->dataTransformer);
    }

    /**
     * Configure form custom options.
     *
     * @param OptionsResolver $resolver
     *
     * @return void
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Inherit from parent form type "data_class"
            'inherit_data' => true
        ]);
    }
}
