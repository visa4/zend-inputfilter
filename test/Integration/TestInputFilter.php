<?php
namespace ZendTest\InputFilter\Integration;

use Zend\InputFilter\Input;
use Zend\InputFilter\InputFilter as BaseInputFilter;

/**
 * Class InputFilter
 */
class InputFilter extends BaseInputFilter
{
    /**
     * InputFilter constructor.
     */
    public function __construct()
    {
        $this->add(new Input('test10'));
    }
}