<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Entity\Task;
use App\Form\Type\Base\BaseTaskType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class EditTaskType
 *
 * Manage task modification (edit/update) form data.
 */
class EditTaskType extends AbstractType
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
            ->add('task', BaseTaskType::class, [
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
            'data_class'    => Task::class,
            'csrf_token_id' => 'edit_task_action'
        ]);
    }
}
