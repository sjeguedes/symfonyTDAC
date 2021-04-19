<?php

declare(strict_types=1);

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class LoginUserType
 *
 * Manage user login action form data.
 */
class LoginUserType extends AbstractType
{
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
        // Define form fields names as expected in UserPasswordFormAuthenticationListener by default
        $builder
            ->add('_username', TextType::class, [
                'label' => 'Nom d\'utilisateur :'
            ])
            ->add('_password', PasswordType::class, [
                'label' => 'Mot de passe :'
            ]);
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
            // Define CSRF token id and filed name as expected in UsernamePasswordFormAuthenticationListener by default
            'csrf_field_name' => '_csrf_token',
            'csrf_token_id'   => 'authenticate'

        ]);
    }

    /**
     * Cancel form name to return form as expected in UserPasswordFormAuthenticationListener by default.
     *
     * {@inheritdoc}
     *
     * @return void
     */
    public function getBlockPrefix(): void
    {
        return;
    }
}
