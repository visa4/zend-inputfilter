<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace ZendTest\InputFilter;

use Interop\Container\ContainerInterface;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use PHPUnit_Framework_TestCase as TestCase;
use Zend\Filter;
use Zend\InputFilter\CollectionInputFilter;
use Zend\InputFilter\Exception\InvalidArgumentException;
use Zend\InputFilter\Exception\RuntimeException;
use Zend\InputFilter\Factory;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter;
use Zend\InputFilter\InputFilterInterface;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\InputFilter\InputFilterProviderInterface;
use Zend\InputFilter\InputInterface;
use Zend\InputFilter\InputProviderInterface;
use Zend\ServiceManager;
use Zend\Validator;
use ZendTest\InputFilter\TestAsset\CustomInput;

/**
 * @covers Zend\InputFilter\Factory
 */
class FactoryTest extends TestCase
{
    public function testCreateInputWithInvalidDataTypeThrowsInvalidArgumentException()
    {
        $factory = $this->createDefaultFactory();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects an array or Traversable; received "string"'
        );
        /** @noinspection PhpParamsInspection */
        $factory->createInput('invalid_value');
    }

    public function testCreateInputWithTypeAsAnUnknownPluginAndNotExistsAsClassNameThrowException()
    {
        $type = 'foo';
        /** @var InputFilterPluginManager|MockObject $pluginManager */
        $pluginManager = $this->getMockBuilder(InputFilterPluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $pluginManager->expects($this->atLeastOnce())
            ->method('has')
            ->with($type)
            ->willReturn(false);

        $factory = new Factory($pluginManager);

        $this->setExpectedException(
            RuntimeException::class,
            'Input factory expects the "type" to be a valid class or a plugin name; received "foo"'
        );
        $factory->createInput(
            [
                'type' => $type,
            ]
        );
    }

    public function testGetInputFilterManagerSettedByItsSetter()
    {
        $pluginManager = $this->getMockBuilder(InputFilterPluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factory = new Factory();
        $factory->setInputFilterManager($pluginManager);
        $this->assertSame($pluginManager, $factory->getInputFilterManager());
    }

    public function testGetInputFilterManagerWhenYouConstructFactoryWithIt()
    {
        $pluginManager = $this->getMockBuilder(InputFilterPluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $factory = new Factory($pluginManager);
        $this->assertSame($pluginManager, $factory->getInputFilterManager());
    }

    public function testCreateInputWithTypeAsAnInvalidClassInstanceThrowException()
    {
        $factory = $this->createDefaultFactory();
        $type = 'stdClass';

        $this->setExpectedException(
            RuntimeException::class,
            'Input factory expects the "type" to be a valid class or a plugin name; received "stdClass"'
        );
        $factory->createInput(
            [
                'type' => $type,
            ]
        );
    }

    public function testCreateInputWithFiltersAsAnInvalidTypeThrowException()
    {
        $factory = $this->createDefaultFactory();

        $this->setExpectedException(
            RuntimeException::class,
            'expects the value associated with "filters" to be an array/Traversable of filters or filter specifications,' .
            ' or a FilterChain; received "string"'
        );
        $factory->createInput(
            [
                'filters' => 'invalid_value',
            ]
        );
    }

    public function testCreateInputWithFiltersAsAnSpecificationWithMissingNameThrowException()
    {
        $factory = $this->createDefaultFactory();

        $this->setExpectedException(
            RuntimeException::class,
            'Invalid filter specification provided; does not include "name" key'
        );
        $factory->createInput(
            [
                'filters' => [
                    [
                        // empty
                    ]
                ],
            ]
        );
    }

    public function testCreateInputWithFiltersAsAnCollectionOfInvalidTypesThrowException()
    {
        $factory = $this->createDefaultFactory();

        $this->setExpectedException(
            RuntimeException::class,
            'Invalid filter specification provided; was neither a filter instance nor an array specification'
        );
        $factory->createInput(
            [
                'filters' => [
                    'invalid value'
                ],
            ]
        );
    }

    public function testCreateInputWithValidatorsAsAnInvalidTypeThrowException()
    {
        $factory = $this->createDefaultFactory();

        $this->setExpectedException(
            RuntimeException::class,
            'expects the value associated with "validators" to be an array/Traversable of validators or validator ' .
            'specifications, or a ValidatorChain; received "string"'
        );
        $factory->createInput(
            [
                'validators' => 'invalid_value',
            ]
        );
    }

    public function testCreateInputWithValidatorsAsAnSpecificationWithMissingNameThrowException()
    {
        $factory = $this->createDefaultFactory();

        $this->setExpectedException(
            RuntimeException::class,
            'Invalid validator specification provided; does not include "name" key'
        );
        $factory->createInput(
            [
                'validators' => [
                    [
                        // empty
                    ]
                ],
            ]
        );
    }

    public function inputTypeSpecificationProvider()
    {
        return [
            // Description => [$specificationKey]
            'continue_if_empty' => ['continue_if_empty'],
            'fallback_value' => ['fallback_value'],
        ];
    }

    /**
     * @dataProvider inputTypeSpecificationProvider
     */
    public function testCreateInputWithSpecificInputTypeSettingsThrowException($specificationKey)
    {
        $factory = $this->createDefaultFactory();
        $type = 'pluginInputInterface';

        $pluginManager = $this->createInputFilterPluginManagerMockForPlugin($type, $this->getMock(InputInterface::class));
        $factory->setInputFilterManager($pluginManager);

        $this->setExpectedException(
            RuntimeException::class,
            sprintf('"%s" can only set to inputs of type "Zend\InputFilter\Input"', $specificationKey)
        );
        $factory->createInput(
            [
                'type' => $type,
                $specificationKey => true,
            ]
        );
    }

    public function testCreateInputWithValidatorsAsAnCollectionOfInvalidTypesThrowException()
    {
        $factory = $this->createDefaultFactory();

        $this->setExpectedException(
            RuntimeException::class,
            'Invalid validator specification provided; was neither a validator instance nor an array specification'
        );
        $factory->createInput(
            [
                'validators' => [
                    'invalid value'
                ],
            ]
        );
    }

    public function testCreateInputFilterWithInvalidDataTypeThrowsInvalidArgumentException()
    {
        $factory = $this->createDefaultFactory();

        $this->setExpectedException(
            InvalidArgumentException::class,
            'expects an array or Traversable; received "string"'
        );
        /** @noinspection PhpParamsInspection */
        $factory->createInputFilter('invalid_value');
    }

    public function testFactoryComposesFilterChainByDefault()
    {
        $factory = $this->createDefaultFactory();
        $this->assertInstanceOf(Filter\FilterChain::class, $factory->getDefaultFilterChain());
    }

    public function testFactoryComposesValidatorChainByDefault()
    {
        $factory = $this->createDefaultFactory();
        $this->assertInstanceOf(Validator\ValidatorChain::class, $factory->getDefaultValidatorChain());
    }

    public function testFactoryAllowsInjectingFilterChain()
    {
        $factory     = $this->createDefaultFactory();
        $filterChain = new Filter\FilterChain();
        $factory->setDefaultFilterChain($filterChain);
        $this->assertSame($filterChain, $factory->getDefaultFilterChain());
    }

    public function testFactoryAllowsInjectingValidatorChain()
    {
        $factory        = $this->createDefaultFactory();
        $validatorChain = new Validator\ValidatorChain();
        $factory->setDefaultValidatorChain($validatorChain);
        $this->assertSame($validatorChain, $factory->getDefaultValidatorChain());
    }

    public function testFactoryUsesComposedFilterChainWhenCreatingNewInputObjects()
    {
        $smMock = $this->getMock(ContainerInterface::class);

        $factory       = $this->createDefaultFactory();
        $filterChain   = new Filter\FilterChain();
        $pluginManager = new Filter\FilterPluginManager($smMock);
        $filterChain->setPluginManager($pluginManager);
        $factory->setDefaultFilterChain($filterChain);
        $input = $factory->createInput([
            'name' => 'foo',
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $inputFilterChain = $input->getFilterChain();
        $this->assertNotSame($filterChain, $inputFilterChain);
        $this->assertSame($pluginManager, $inputFilterChain->getPluginManager());
    }

    public function testFactoryUsesComposedValidatorChainWhenCreatingNewInputObjects()
    {
        $smMock = $this->getMock(ContainerInterface::class);
        $factory          = $this->createDefaultFactory();
        $validatorChain   = new Validator\ValidatorChain();
        $validatorPlugins = new Validator\ValidatorPluginManager($smMock);
        $validatorChain->setPluginManager($validatorPlugins);
        $factory->setDefaultValidatorChain($validatorChain);
        $input = $factory->createInput([
            'name' => 'foo',
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $inputValidatorChain = $input->getValidatorChain();
        $this->assertNotSame($validatorChain, $inputValidatorChain);
        $this->assertSame($validatorPlugins, $inputValidatorChain->getPluginManager());
    }

    public function testFactoryInjectsComposedFilterAndValidatorChainsIntoInputObjectsWhenCreatingNewInputFilterObjects()
    {
        $smMock = $this->getMock(ContainerInterface::class);
        $factory          = $this->createDefaultFactory();
        $filterPlugins    = new Filter\FilterPluginManager($smMock);
        $validatorPlugins = new Validator\ValidatorPluginManager($smMock);
        $filterChain      = new Filter\FilterChain();
        $validatorChain   = new Validator\ValidatorChain();
        $filterChain->setPluginManager($filterPlugins);
        $validatorChain->setPluginManager($validatorPlugins);
        $factory->setDefaultFilterChain($filterChain);
        $factory->setDefaultValidatorChain($validatorChain);

        $inputFilter = $factory->createInputFilter([
            'foo' => [
                'name' => 'foo',
            ],
        ]);
        $this->assertInstanceOf(InputFilterInterface::class, $inputFilter);
        $this->assertEquals(1, count($inputFilter));
        $input = $inputFilter->get('foo');
        $this->assertInstanceOf(InputInterface::class, $input);
        $inputFilterChain    = $input->getFilterChain();
        $inputValidatorChain = $input->getValidatorChain();
        $this->assertSame($filterPlugins, $inputFilterChain->getPluginManager());
        $this->assertSame($validatorPlugins, $inputValidatorChain->getPluginManager());
    }

    public function testFactoryWillCreateInputWithSuggestedFilters()
    {
        $factory      = $this->createDefaultFactory();
        $htmlEntities = new Filter\HtmlEntities();
        $input = $factory->createInput([
            'name'    => 'foo',
            'filters' => [
                [
                    'name' => Filter\StringTrim::class
                ],
                $htmlEntities,
                [
                    'name' => Filter\StringToLower::class,
                    'options' => [
                        'encoding' => 'ISO-8859-1',
                    ],
                ],
            ],
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertEquals('foo', $input->getName());
        $chain = $input->getFilterChain();
        $index = 0;
        foreach ($chain as $filter) {
            switch ($index) {
                case 0:
                    $this->assertInstanceOf(Filter\StringTrim::class, $filter);
                    break;
                case 1:
                    $this->assertSame($htmlEntities, $filter);
                    break;
                case 2:
                    $this->assertInstanceOf(Filter\StringToLower::class, $filter);
                    $this->assertEquals('ISO-8859-1', $filter->getEncoding());
                    break;
                default:
                    $this->fail('Found more filters than expected');
            }
            $index++;
        }
    }

    public function testFactoryWillCreateInputWithSuggestedValidators()
    {
        $factory = $this->createDefaultFactory();
        $digits  = new Validator\Digits();
        $input = $factory->createInput([
            'name'       => 'foo',
            'validators' => [
                [
                    'name' => Validator\NotEmpty::class,
                ],
                $digits,
                [
                    'name' => Validator\StringLength::class,
                    'options' => [
                        'min' => 3,
                        'max' => 5,
                    ],
                ],
            ],
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertEquals('foo', $input->getName());
        $chain = $input->getValidatorChain();
        $index = 0;
        foreach ($chain as $validator) {
            switch ($index) {
                case 0:
                    $this->assertInstanceOf(Validator\NotEmpty::class, $validator);
                    break;
                case 1:
                    $this->assertSame($digits, $validator);
                    break;
                case 2:
                    $this->assertInstanceOf(Validator\StringLength::class, $validator);
                    $this->assertEquals(3, $validator->getMin());
                    $this->assertEquals(5, $validator->getMax());
                    break;
                default:
                    $this->fail('Found more validators than expected');
            }
            $index++;
        }
    }

    public function testFactoryWillCreateInputWithSuggestedRequiredFlagAndAlternativeAllowEmptyFlag()
    {
        $factory = $this->createDefaultFactory();
        $input   = $factory->createInput([
            'name'     => 'foo',
            'required' => false,
            'allow_empty' => false,
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertFalse($input->isRequired());
        $this->assertFalse($input->allowEmpty());
    }

    public function testFactoryWillCreateInputWithSuggestedAllowEmptyFlagAndImpliesRequiredFlag()
    {
        $factory = $this->createDefaultFactory();
        $input   = $factory->createInput([
            'name'        => 'foo',
            'allow_empty' => true,
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertTrue($input->allowEmpty());
        $this->assertFalse($input->isRequired());
    }

    public function testFactoryWillCreateInputWithSuggestedName()
    {
        $factory = $this->createDefaultFactory();
        $input   = $factory->createInput([
            'name'        => 'foo',
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertEquals('foo', $input->getName());
    }

    public function testFactoryWillCreateInputWithContinueIfEmptyFlag()
    {
        $factory = $this->createDefaultFactory();
        $input = $factory->createInput([
            'name'              => 'foo',
            'continue_if_empty' => true,
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $this->assertTrue($input->continueIfEmpty());
    }

    public function testFactoryAcceptsInputInterface()
    {
        $factory = $this->createDefaultFactory();
        $input = new Input();

        $inputFilter = $factory->createInputFilter([
            'foo' => $input
        ]);

        $this->assertInstanceOf(InputFilterInterface::class, $inputFilter);
       // $this->assertTrue($inputFilter->has('foo'));
        $this->assertEquals($input, $inputFilter->get('foo'));
    }

    public function testFactoryAcceptsInputFilterInterface()
    {
        $factory = $this->createDefaultFactory();
        $input = new InputFilter();

        $inputFilter = $factory->createInputFilter([
            'foo' => $input
        ]);

        $this->assertInstanceOf(InputFilterInterface::class, $inputFilter);
        $this->assertTrue($inputFilter->has('foo'));
        $this->assertEquals($input, $inputFilter->get('foo'));
    }

    public function testFactoryWillCreateInputFilterAndAllInputObjectsFromGivenConfiguration()
    {
        $factory     = $this->createDefaultFactory();
        $factory->getInputFilterManager()->setInvokableClass(CustomInput::class, CustomInput::class);
        $inputFilter = $factory->createInputFilter([
            'foo' => [
                'name'       => 'foo',
                'required'   => false,
                'validators' => [
                    [
                        'name' => Validator\NotEmpty::class,
                    ],
                    [
                        'name' => Validator\StringLength::class,
                        'options' => [
                            'min' => 3,
                            'max' => 5,
                        ],
                    ],
                ],
            ],
            'bar' => [
                'allow_empty' => true,
                'filters'     => [
                    [
                        'name' => Filter\StringTrim::class,
                    ],
                    [
                        'name' => Filter\StringToLower::class,
                        'options' => [
                            'encoding' => 'ISO-8859-1',
                        ],
                    ],
                ],
            ],
            'baz' => [
                'type'   => InputFilter::class,
                'foo' => [
                    'name'       => 'foo',
                    'required'   => false,
                    'validators' => [
                        [
                            'name' => Validator\NotEmpty::class,
                        ],
                        [
                            'name' => Validator\StringLength::class,
                            'options' => [
                                'min' => 3,
                                'max' => 5,
                            ],
                        ],
                    ],
                ],
                'bar' => [
                    'allow_empty' => true,
                    'filters'     => [
                        [
                            'name' => Filter\StringTrim::class
                        ],
                        [
                            'name' => Filter\StringToLower::class,
                            'options' => [
                                'encoding' => 'ISO-8859-1',
                            ],
                        ],
                    ],
                ],
            ],
            'bat' => [
                'type' => 'ZendTest\InputFilter\TestAsset\CustomInput',
                'name' => 'bat',
            ],
            'zomg' => [
                'name' => 'zomg',
                'continue_if_empty' => true,
            ],
        ]);
        $this->assertInstanceOf(InputFilter::class, $inputFilter);
        $this->assertEquals(5, count($inputFilter));

        foreach (['foo', 'bar', 'baz', 'bat', 'zomg'] as $name) {
            $input = $inputFilter->get($name);

            switch ($name) {
                case 'foo':
                    $this->assertInstanceOf(Input::class, $input);
                    $this->assertFalse($input->isRequired());
                    $this->assertEquals(2, count($input->getValidatorChain()));
                    break;
                case 'bar':
                    $this->assertInstanceOf(Input::class, $input);
                    $this->assertTrue($input->allowEmpty());
                    $this->assertEquals(2, count($input->getFilterChain()));
                    break;
                case 'baz':
                    $this->assertInstanceOf(InputFilter::class, $input);
                    $this->assertEquals(2, count($input));
                    $foo = $input->get('foo');
                    $this->assertInstanceOf(Input::class, $foo);
                    $this->assertFalse($foo->isRequired());
                    $this->assertEquals(2, count($foo->getValidatorChain()));
                    $bar = $input->get('bar');
                    $this->assertInstanceOf(Input::class, $bar);
                    $this->assertTrue($bar->allowEmpty());
                    $this->assertEquals(2, count($bar->getFilterChain()));
                    break;
                case 'bat':
                    $this->assertInstanceOf('ZendTest\InputFilter\TestAsset\CustomInput', $input);
                    $this->assertEquals('bat', $input->getName());
                    break;
                case 'zomg':
                    $this->assertInstanceOf(Input::class, $input);
                    $this->assertTrue($input->continueIfEmpty());
            }
        }
    }

    public function testFactoryWillCreateInputFilterMatchingInputNameWhenNotSpecified()
    {
        $factory     = $this->createDefaultFactory();
        $inputFilter = $factory->createInputFilter([
            ['name' => 'foo']
        ]);

        $this->assertTrue($inputFilter->has('foo'));
        $this->assertInstanceOf(Input::class, $inputFilter->get('foo'));
    }

    public function testFactoryAllowsPassingValidatorChainsInInputSpec()
    {
        $factory = $this->createDefaultFactory();
        $chain   = new Validator\ValidatorChain();
        $input   = $factory->createInput([
            'name'       => 'foo',
            'validators' => $chain,
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $test = $input->getValidatorChain();
        $this->assertSame($chain, $test);
    }

    public function testFactoryAllowsPassingFilterChainsInInputSpec()
    {
        $factory = $this->createDefaultFactory();
        $chain   = new Filter\FilterChain();
        $input   = $factory->createInput([
            'name'    => 'foo',
            'filters' => $chain,
        ]);
        $this->assertInstanceOf(InputInterface::class, $input);
        $test = $input->getFilterChain();
        $this->assertSame($chain, $test);
    }

    public function testFactoryAcceptsCollectionInputFilter()
    {
        $factory = $this->createDefaultFactory();

        /** @var CollectionInputFilter $inputFilter */
        $inputFilter = $factory->createInputFilter([
            'type'        => CollectionInputFilter::class,
            'required'    => true,
            'inputfilter' => new InputFilter(),
            'count'       => 3,
        ]);

        $this->assertInstanceOf(CollectionInputFilter::class, $inputFilter);
        $this->assertInstanceOf(InputFilter::class, $inputFilter->getInputFilter());
        $this->assertTrue($inputFilter->getIsRequired());
        $this->assertEquals(3, $inputFilter->getCount());
    }

    public function testFactoryWillCreateInputWithErrorMessage()
    {
        $factory = $this->createDefaultFactory();
        $input   = $factory->createInput([
            'name'          => 'foo',
            'error_message' => 'My custom error message',
        ]);
        $this->assertEquals('My custom error message', $input->getErrorMessage());
    }

    public function testFactoryWillNotGetPrioritySetting()
    {
        //Reminder: Priority at which to enqueue filter; defaults to 1000 (higher executes earlier)
        $factory = $this->createDefaultFactory();
        $input   = $factory->createInput([
            'name'    => 'foo',
            'filters' => [
                [
                    'name'      => 'StringTrim',
                    'priority'  => Filter\FilterChain::DEFAULT_PRIORITY - 1 // 999
                ],
                [
                    'name'      => 'StringToUpper',
                    'priority'  => Filter\FilterChain::DEFAULT_PRIORITY + 1 //1001
                ],
                [
                    'name'      => 'StringToLower' // default priority 1000
                ]
            ]
        ]);

        // We should have 3 filters
        $this->assertEquals(3, $input->getFilterChain()->count());

        // Filters should pop in the following order:
        // string_to_upper (1001), string_to_lower (1000), string_trim (999)
        $index = 0;
        foreach ($input->getFilterChain()->getFilters() as $filter) {
            switch ($index) {
                case 0:
                    $this->assertInstanceOf(Filter\StringToUpper::class, $filter);
                    break;
                case 1:
                    $this->assertInstanceOf(Filter\StringToLower::class, $filter);
                    break;
                case 2:
                    $this->assertInstanceOf(Filter\StringTrim::class, $filter);
                    break;
            }
            $index++;
        }
    }

    public function testConflictNameWithInputFilterType()
    {
        $factory = $this->createDefaultFactory();

        $inputFilter = $factory->createInputFilter(
            [
                'type' => [
                    'required' => true
                ]
            ]
        );

        $this->assertInstanceOf(InputFilter::class, $inputFilter);
        $this->assertTrue($inputFilter->has('type'));
    }

    public function testCustomFactoryInCollection()
    {
        $factory = new TestAsset\CustomFactory();
        /** @var CollectionInputFilter $inputFilter */
        $inputFilter = $factory->createInputFilter([
            'type'        => 'collection',
            'input_filter' => new InputFilter(),
        ]);
        $this->assertInstanceOf(TestAsset\CustomFactory::class, $inputFilter->getFactory());
    }

    /**
     * @group 4838
     */
    public function testCanSetInputErrorMessage()
    {
        $factory = $this->createDefaultFactory();
        $input   = $factory->createInput([
            'name'          => 'test',
            'type'          => Input::class,
            'error_message' => 'Custom error message',
        ]);
        $this->assertEquals('Custom error message', $input->getErrorMessage());
    }

    public function testSetInputFilterManagerWithServiceManager()
    {
        $serviceManager = new ServiceManager\ServiceManager;
        $inputFilterManager = new InputFilterPluginManager($serviceManager);
        $serviceManager->setService('ValidatorManager', new Validator\ValidatorPluginManager($serviceManager));
        $serviceManager->setService('FilterManager', new Filter\FilterPluginManager($serviceManager));
        $factory = new Factory($inputFilterManager);
        $this->assertInstanceOf(
            Validator\ValidatorPluginManager::class,
            $factory->getDefaultValidatorChain()->getPluginManager()
        );
        $this->assertInstanceOf(
            Filter\FilterPluginManager::class,
            $factory->getDefaultFilterChain()->getPluginManager()
        );
    }

    public function testSetInputFilterManagerWithoutServiceManager()
    {
        $smMock = $this->getMock(ContainerInterface::class);
        $inputFilterManager = new InputFilterPluginManager($smMock);
        $factory = new Factory($inputFilterManager);
        $this->assertSame($inputFilterManager, $factory->getInputFilterManager());
    }

    public function testSetInputFilterManagerOnConstruct()
    {
        $smMock = $this->getMock(ContainerInterface::class);
        $inputFilterManager = new InputFilterPluginManager($smMock);
        $factory = new Factory($inputFilterManager);
        $this->assertSame($inputFilterManager, $factory->getInputFilterManager());
    }

    /**
     * @group 5691
     *
     * @covers \Zend\InputFilter\Factory::createInput
     */
    public function testSetsBreakChainOnFailure()
    {
        $factory = $this->createDefaultFactory();

        $this->assertTrue($factory->createInput(['break_on_failure' => true])->breakOnFailure());

        $this->assertFalse($factory->createInput(['break_on_failure' => false])->breakOnFailure());
    }

    public function testCanCreateInputFilterWithNullInputs()
    {
        $factory = $this->createDefaultFactory();

        $inputFilter = $factory->createInputFilter([
            'foo' => [
                'name' => 'foo',
            ],
            'bar' => null,
            'baz' => [
                'name' => 'baz',
            ],
        ]);

        $this->assertInstanceOf(InputFilter::class, $inputFilter);
        $this->assertEquals(2, count($inputFilter));
        $this->assertTrue($inputFilter->has('foo'));
        $this->assertFalse($inputFilter->has('bar'));
        $this->assertTrue($inputFilter->has('baz'));
    }

    /**
     * @group 7010
     */
    public function testCanCreateInputFromProvider()
    {
        /** @var InputProviderInterface|MockObject $provider */
        $provider = $this->getMock(InputProviderInterface::class, ['getInputSpecification']);

        $provider
            ->expects($this->any())
            ->method('getInputSpecification')
            ->will($this->returnValue(['name' => 'foo']));

        $factory = $this->createDefaultFactory();
        $input   = $factory->createInput($provider);

        $this->assertInstanceOf(InputInterface::class, $input);
    }

    /**
     * @group 7010
     */
    public function testCanCreateInputFilterFromProvider()
    {
        /** @var InputFilterProviderInterface|MockObject $provider */
        $provider = $this->getMock(
            InputFilterProviderInterface::class,
            ['getInputFilterSpecification']
        );
        $provider
            ->expects($this->any())
            ->method('getInputFilterSpecification')
            ->will($this->returnValue([
                'foo' => [
                    'name'       => 'foo',
                    'required'   => false,
                ],
                'baz' => [
                    'name'       => 'baz',
                    'required'   => true,
                ],
            ]));

        $factory     = $this->createDefaultFactory();
        $inputFilter = $factory->createInputFilter($provider);

        $this->assertInstanceOf(InputFilterInterface::class, $inputFilter);
    }

    public function testSuggestedTypeMayBePluginNameInInputFilterPluginManager()
    {
        $serviceManager = new ServiceManager\ServiceManager();
        $pluginManager = new InputFilterPluginManager($serviceManager);
        $pluginManager->setService('bar', new Input('bar'));
        $factory = new Factory($pluginManager);

        $input = $factory->createInput([
            'type' => 'bar'
        ]);
        $this->assertSame('bar', $input->getName());
    }

    public function testInputFromPluginManagerMayBeFurtherConfiguredWithSpec()
    {
        $pluginManager = new InputFilterPluginManager(new ServiceManager\ServiceManager());
        $pluginManager->setService('bar', $barInput = new Input('bar'));
        $factory = new Factory($pluginManager);
        $this->assertTrue($barInput->isRequired());
        $factory->setInputFilterManager($pluginManager);

        $input = $factory->createInput([
            'type' => 'bar',
            'required' => false
        ]);

        $this->assertFalse($input->isRequired());
        $this->assertSame('bar', $input->getName());
    }

    /**
     * @return Factory
     */
    protected function createDefaultFactory()
    {
        $factory = new Factory();

        return $factory;
    }

    /**
     * @param string $pluginName
     * @param mixed $pluginValue
     *
     * @return MockObject|InputFilterPluginManager
     */
    protected function createInputFilterPluginManagerMockForPlugin($pluginName, $pluginValue)
    {
        /** @var InputFilterPluginManager|MockObject $pluginManager */
        $pluginManager = $this->getMockBuilder(InputFilterPluginManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $pluginManager->expects($this->atLeastOnce())
            ->method('has')
            ->with($pluginName)
            ->willReturn(true)
        ;
        $pluginManager->expects($this->atLeastOnce())
            ->method('get')
            ->with($pluginName)
            ->willReturn($pluginValue)
        ;
        return $pluginManager;
    }
}
