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
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BaseUserType
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
                'label' => "Nom d'utilisateur"
            ])
            ->add('password', RepeatedType::class, [
                'type'            => PasswordType::class,
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
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email'
            ])
            ->add('roles', ChoiceType::class, [
                'label'        => 'Rôle utilisateur (fonction)',
                'choices'      => [
                    'Gestionnaire de tâche' => User::ROLES['user'],
                    'Administrateur'        => User::ROLES['admin']
                ],
                'choice_value' => fn (string $value): string => array_search($value, User::ROLES)
            ]);
        // Transform roles data to get an array of string
        $builder
            ->get('roles')
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
            'data_class'    => User::class,
            'csrf_token_id' => 'user_action' // TODO: transfer and modify this later per action!
        ]);
    }
}
