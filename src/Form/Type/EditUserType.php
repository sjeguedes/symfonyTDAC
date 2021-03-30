<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\User;
use App\Form\Type\Base\BaseUserType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class EditUserType
 *
 * Manage user modification (edit/update) form data.
 */
class EditUserType extends AbstractType
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
        $builder
            ->add('user', BaseUserType::class, [
                'label' => false
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
            'data_class'    => User::class,
            'csrf_token_id' => 'edit_user_action'
        ]);
    }
}
