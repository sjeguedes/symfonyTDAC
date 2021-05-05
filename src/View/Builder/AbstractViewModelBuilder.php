<?php

declare(strict_types=1);

namespace App\View\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

/**
 * Class AbstractViewModelBuilder
 *
 * Manage all view model builder common actions.
 */
abstract class AbstractViewModelBuilder implements ViewModelBuilderInterface
{
    /**
     * @var EntityManagerInterface
     */
    protected EntityManagerInterface $entityManager;

    /**
     * @var FormFactoryInterface
     */
    protected FormFactoryInterface $formFactory;

    /**
     * @var \StdClass
     */
    protected \StdClass $viewModel;

    /**
     * AbstractViewModelBuilder constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param FormFactoryInterface   $formFactory
     */
    public function __construct(EntityManagerInterface $entityManager, FormFactoryInterface $formFactory)
    {
        $this->entityManager = $entityManager;
        $this->formFactory = $formFactory;
        // Prepare a standard DTO to store view data
        $this->viewModel = new \StdClass();
    }

    /**
     * {@inheritdoc}
     *
     * @return object|\StdClass
     */
    public function create(string $viewReference = null, array $mergedData = []): object
    {
        // Jump loop if it is unnecessary.
        if (empty($mergedData)) {
            goto jumpedLoop;
        }
        // Add merged data to those which are expected in each case.
        foreach ($mergedData as $key => $value) {
            if ('string' !== \gettype($key)) {
                throw new \InvalidArgumentException('Merged data keys are expected to be a string!');
            }
            // Create a public property ("$this->viewModel->$key = $value;" also works!)
            $this->viewModel->{$key} = $value;
        }
        jumpedLoop:
        // Feed particular data for each view
        return $this->addViewData($viewReference);
    }

    /**
     * Add particular expected data to each view.
     *
     * @param string $viewReference|null a label to determine which view is concerned
     *
     * @return \Stdclass
     */
    abstract protected function addViewData(string $viewReference = null): \Stdclass;

    /**
     * Generate multiple identical form views and optionally keep submitted current form state.
     *
     * @param array<object>      $entities      a particular entity collection
     * @param string             $viewReference a label to select the associated view
     * @param string             $withStatus    a list status to filter and render it
     * @param FormInterface|null $currentForm   a form already in use among the other ones (with form views)
     *
     * @return array|FormView[]
     */
    protected function generateMultipleFormViews(
        array $entities,
        string $viewReference,
        string $withStatus = null,
        FormInterface $currentForm = null
    ): array {
        // Current (submitted) form name does not contain an integer as suffix!
        if (null !== $currentForm && !preg_match('/(\d+)$/', $currentForm->getName(), $matches)) {
            throw new \RuntimeException("Current form name suffix is expected to be an integer as index!");
        }
        $multipleFormViews = [];
        $length = \count($entities);
        $formNamePrefix = $viewReference . '_';
        $suffixIdAsInt = isset($matches) && 2 === \count($matches) ? $matches[1] : null;
        for ($i = 0; $i < $length; $i++) {
            // CAUTION: "id" is returned as string value!
            $entityId = $entities[$i]['id'];
            // Create current (submitted) form view if not null with its actual state!
            if (null !== $suffixIdAsInt && \intval($entityId) === \intval($suffixIdAsInt)) {
                $form = $currentForm;
            } else {
                // Create other identical forms views
                $childClass = \get_called_class();
                $formTypeClassName = $childClass::FORM_TYPES[$viewReference];
                $form = $this->formFactory->createNamed(
                    $formNamePrefix . $entityId,
                    $formTypeClassName
                );
            }
            $formView = $form->createView();
            // Pass "withStatus" data to form view
            null === $withStatus ?: $formView->vars['list_status'] = $withStatus;
            $multipleFormViews[$entityId] = $formView;
        }

        return $multipleFormViews;
    }
}
