<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Manager;

use App\Tests\Unit\Helpers\CustomAssertionsTestCaseTrait;
use App\View\Builder\AbstractViewModelBuilder;
use App\View\Builder\ViewModelBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Class AbstractViewModelBuilderTest
 *
 * Manage unit tests for view model builders common logic declared in AbstractViewModelBuilder class.
 */
class AbstractViewModelBuilderTest extends TestCase
{
    use CustomAssertionsTestCaseTrait;

    /**
     * @var MockObject|EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * @var MockObject|FormFactoryInterface|null
     */
    protected ?FormFactoryInterface $formFactory;

    /**
     * @var ViewModelBuilderInterface|null
     */
    private ?ViewModelBuilderInterface $viewModelBuilder;

    /**
     * @var \StdClass|null
     */
    private ?\StdClass $viewModel;

    /**
     * Setup needed instance(s).
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->entityManager = static::createMock(EntityManagerInterface::class);
        $this->formFactory = static::createMock(FormFactoryInterface::class);
        $this->viewModel = null;
        // Use an anonymous class to represent a concrete view model builder
        $this->viewModelBuilder = new class (
            $this->entityManager,
            $this->formFactory
        ) extends AbstractViewModelBuilder {
            /**
             * Return the view model to test its hydrating with merged data.
             *
             * {@inheritdoc}
             */
            protected function addViewData(string $viewReference = null): \Stdclass
            {
                return $this->viewModel;
            }
        };
    }

    /**
     * Provide a set of data to check "create" method successful view model build.
     *
     * @return \Generator
     */
    public function provideDataToCheckSuccessfulBuild(): \Generator
    {
        yield [
            'Builds a view model without view reference' => [
                'view_reference' => null,
                'merged_data'    => ['key' => 'value']
            ]
        ];
        yield [
            'Builds a view model without merged data' => [
                'view_reference' => 'view_reference',
                'merged_data'    => []
            ]
        ];
        yield [
            'Builds a view model without both view reference and merged data' => [
                'view_reference' => null,
                'merged_data'    => []
            ]
        ];
    }

    /**
     * Check that view model is correctly built.
     *
     * @dataProvider provideDataToCheckSuccessfulBuild
     *
     * @param array $data
     *
     * @return void
     */
    public function testViewModelCreationIsOk(array $data): void
    {
        $viewModel = $this->viewModelBuilder->create($data['view_reference'], $data['merged_data']);
        $this->viewModel = $viewModel;
        static::assertInstanceOf(\StdClass::class, $viewModel);
        // check that merged data are retrieved as view model public properties
        if (empty($data['merged_data'])) goto end;
        array_map(
            fn ($value) => static::assertObjectHasAttribute($value, $this->viewModel),
            array_flip($data['merged_data'])
        );
        end:
    }

    /**
     * Check that view model build fails if at least one merged data key is not of string type.
     *
     * @return void
     */
    public function testViewModelCreationIsNotOkWhenMergedDataKeyIsNotOfStringType(): void
    {
        static::expectException(\InvalidArgumentException::class);
        $this->viewModelBuilder->create(null, ['string' => 'value1', 0 => 'value2']);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->entityManager = null;
        $this->formFactory = null;
        $this->viewModel = null;
        $this->viewModelBuilder = null;
    }
}