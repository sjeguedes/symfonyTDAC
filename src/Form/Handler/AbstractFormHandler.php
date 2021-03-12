<?php

declare(strict_types=1);

namespace App\Form\Handler;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class AbstractFormHandler
 *
 * Manage all form handler common actions.
 */
abstract class AbstractFormHandler implements FormHandlerInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected FormFactoryInterface $formFactory;

    /**
     * @var object|null
     */
    private ?object $clonedOriginalModel;

    /**
     * @var FormInterface|null
     */
    private ?FormInterface $form;

    /**
     * @var string
     */
    protected string $formTypeClassName;

    /**
     * @var FlashBagInterface
     */
    protected FlashBagInterface $flashBag;

    /**
     * @var bool|null
     */
    private ?bool $successState;

    /**
     * @var object|null
     */
    private ?object $dataModel;

    /**
     * CreateTaskFormHandler constructor.
     *
     * @param FormFactoryInterface $formFactory
     * @param string               $formTypeClassName a Fully Qualified Class Name (F.Q.C.N)
     * @param FlashBagInterface    $flashBag
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        string $formTypeClassName,
        FlashBagInterface $flashBag
    ) {
        $this->formFactory = $formFactory;
        $this->formTypeClassName = $formTypeClassName;
        $this->flashBag = $flashBag;
        $this->clonedOriginalModel = null;
        $this->form = null;
        $this->successState = false;
        $this->dataModel = null;
    }

    /**
     * Retrieve cloned initial data model.
     *
     * @return object|null
     */
    protected function getClonedOriginalModel(): ?object
    {
        return $this->clonedOriginalModel;
    }

    /**
     * Retrieve corresponding data model.
     *
     * @return object|null
     */
    protected function getDataModel(): ?object
    {
        return $this->dataModel;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function isSuccess(): bool
    {
        if (null === $this->form) {
            throw new \RuntimeException('Form must be processed first!');
        }

        return $this->successState;
    }

    /**
     * {@inheritdoc}
     *
     * @return object|FormInterface
     */
    public function process(object $request, array $data = [], array $formOptions = []): object
    {
        if (!isset($data['dataModel'])) {
            throw new \RuntimeException('A "dataModel" key must be defined!');
        }
        // Get a clone of original passed model for later comparison
        $this->clonedOriginalModel = clone $data['dataModel'];
        // Create a form based on a form type reference (Symfony way)
        $this->form = $this->formFactory->create($this->formTypeClassName, $data['dataModel'], $formOptions);
        // Handle a request to evaluate form state
        $this->form->handleRequest($request);
        // Set success state
        if ($this->form->isSubmitted()) {
            $this->successState = $this->form->isValid() ? true : false;
        }
        // Set data model permanently
        $this->dataModel = $this->form->getData();

        return $this->form;
    }
}