<?php

declare(strict_types=1);

namespace App\View\Builder;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;

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
        // Jump loop if it is unnecessary
        if (empty($mergedData)) goto jumpedLoop;
        // Add merged data to those which are expected in each case
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
     * @param string $viewReference a label to determine which view is concerned
     *
     * @return \Stdclass
     */
    abstract protected function addViewData(string $viewReference): \Stdclass;
}