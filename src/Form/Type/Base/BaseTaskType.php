<?php

declare(strict_types=1);

namespace App\Form\Type\Base;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class BaseTaskType
 *
 * Manage task actions common form data.
 */
class BaseTaskType extends AbstractType
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
            ->add('title', TextType::class, [
                'label'      => 'Titre',
                // This is a tip not to pass "null" value to entity setter!
                // Avoid issue on update without transformation when task title is empty (null).
                'empty_data' => ''
            ])
            ->add('content', TextareaType::class, [
                'label'      => 'Contenu',
                // This is a tip not to pass "null" value to entity setter!
                // Avoid issue on update without transformation when task content is empty (null).
                'empty_data' => ''
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
            // Inherit from parent form type "data_class"
            'inherit_data' => true
        ]);
    }
}
