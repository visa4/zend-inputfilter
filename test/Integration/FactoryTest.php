<?php
namespace ZendTest\InputFilter\Integration;

use PHPUnit_Framework_TestCase;
use Zend\InputFilter\Factory;
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
            'error_message' => 'test'
        ];

        $input = $this->factory->createInput($input);
        var_dump($input);
    }
}