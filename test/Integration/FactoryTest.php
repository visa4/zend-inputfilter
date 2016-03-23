<?php
namespace ZendTest\InputFilter\Integration;

use PHPUnit_Framework_TestCase;
use Zend\InputFilter\Factory;
use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilterPluginManager;
use Zend\ServiceManager\ServiceManager;

/**
 * Class FactoryTest
 */
class FactoryTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Factory
     */
    protected $factory;

    protected function setUp()
    {
        $this->factory = new Factory(new InputFilterPluginManager(new ServiceManager()));
    }

    public function testArrayConfigInput()
    {
        $input = [
            'name' =>  'test',
            'required' => true,
            'allow_empty' => true,
            'continue_if_empty' => true,
            'error_message' => 'My custom message',
            'fallback_value' => 'test',
            'break_on_failure' => true,
            'validators' => [
                [
                    'name' => 'notEmpty'
                ],
                [
                    'name' => 'iban'
                ],
            ],
            'filters' => [
                [
                    'name' => 'dir'
                ]
            ],
        ];

        $input = $this->factory->createInput($input);
        $this->assertInstanceOf('Zend\InputFilter\InputInterface', $input);
        /** @var $input \Zend\InputFilter\Input */
        $this->assertSame($input->getName(), 'test');
        $this->assertTrue($input->isRequired());
        $this->assertTrue($input->allowEmpty());
        $this->assertTrue($input->continueIfEmpty());
        $this->assertSame($input->getErrorMessage(), 'My custom message');
        $this->assertSame($input->getFallbackValue(), 'test');
        $this->assertTrue($input->breakOnFailure());
        $this->assertSame(2, count($input->getValidatorChain()->getValidators()));
        $this->assertSame(1, count($input->getFilterChain()->getFilters()));
    }

    public function testObjectConfigInput()
    {
        $input = new Input('test');
        $input->setRequired(true)
            ->setAllowEmpty(true)
            ->setContinueIfEmpty(true)
            ->setErrorMessage('My custom message')
            ->setFallbackValue('test')
            ->setBreakOnFailure(true);

        $input = $this->factory->createInput($input);
        $this->assertInstanceOf('Zend\InputFilter\InputInterface', $input);
        /** @var $input \Zend\InputFilter\Input */
        $this->assertSame($input->getName(), 'test');
        $this->assertTrue($input->isRequired());
        $this->assertTrue($input->allowEmpty());
        $this->assertTrue($input->continueIfEmpty());
        $this->assertSame($input->getErrorMessage(), 'My custom message');
        $this->assertSame($input->getFallbackValue(), 'test');
        $this->assertTrue($input->breakOnFailure());
    }

    public function testArrayConfigInputFilter()
    {
        $inputFilter = [
            [
                'name' => 'input',
            ],
            'fileinput' => [
                'type' => 'fileinput'
            ],
            'inputfilter' => [
                'type' => 'inputfilter',
                [
                    'name' => 'input',
                ],
                'inputfilter' => [
                    'type' => 'inputfilter',
                    [
                        'name' => 'input1',
                    ]
                ],
                'testcollection' => [
                    'type' => 'collection',
                    'name' => 'collection',
                    [
                        'name' => 'inputconllection',
                    ]
                ],
                [
                    'type' => 'collection',
                    'name' => 'collection1',
                    'input_filter' => [
                        [
                            'name' => 'inputconllection1',
                        ],
                    ]
                ]
            ]
        ];

        $inputFilter = $this->factory->createInputFilter($inputFilter);
        $this->assertInstanceOf('Zend\InputFilter\InputFilterInterface', $inputFilter);
        $this->assertSame(3, $inputFilter->count());
        $this->assertInstanceOf('Zend\InputFilter\InputInterface', $inputFilter->get('input'));
        $this->assertInstanceOf('Zend\InputFilter\FileInput', $inputFilter->get('fileinput'));
        $this->assertInstanceOf('Zend\InputFilter\InputFilterInterface', $inputFilter->get('inputfilter'));
        $this->assertSame(4, $inputFilter->get('inputfilter')->count());
        $this->assertInstanceOf('Zend\InputFilter\InputFilterInterface', $inputFilter->get('inputfilter')->get('inputfilter'));
        $this->assertInstanceOf('Zend\InputFilter\CollectionInputFilter', $inputFilter->get('inputfilter')->get('collection'));
        $this->assertInstanceOf('Zend\InputFilter\InputInterface', $inputFilter->get('inputfilter')->get('collection')->getinputFilter()->get('inputconllection'));
        $this->assertInstanceOf('Zend\InputFilter\InputInterface', $inputFilter->get('inputfilter')->get('collection1')->getinputFilter()->get('inputconllection1'));
    }
}