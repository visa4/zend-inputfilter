<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Zend\InputFilter;

use Traversable;
use Zend\Filter\FilterChain;
use Zend\ServiceManager\ServiceManager;
use Zend\Stdlib\ArrayUtils;
use Zend\Validator\ValidatorChain;
use Zend\Validator\ValidatorInterface;

class Factory
{
    /**
     * @var FilterChain
     */
    protected $defaultFilterChain;

    /**
     * @var ValidatorChain
     */
    protected $defaultValidatorChain;

    /**
     * @var InputFilterPluginManager
     */
    protected $inputFilterManager;

    /**
     * @param InputFilterPluginManager $inputFilterManager
     */
    public function __construct(InputFilterPluginManager $inputFilterManager = null)
    {
        $this->defaultFilterChain    = new FilterChain();
        $this->defaultValidatorChain = new ValidatorChain();

        if ($inputFilterManager) {
            $this->setInputFilterManager($inputFilterManager);
        }
    }

    /**
     * Set default filter chain to use
     *
     * @param  FilterChain $filterChain
     * @return Factory
     */
    public function setDefaultFilterChain(FilterChain $filterChain)
    {
        $this->defaultFilterChain = $filterChain;
        return $this;
    }

    /**
     * Get default filter chain, if any
     *
     * @return null|FilterChain
     */
    public function getDefaultFilterChain()
    {
        return $this->defaultFilterChain;
    }

    /**
     * Clear the default filter chain (i.e., don't inject one into new inputs)
     *
     * @return void
     */
    public function clearDefaultFilterChain()
    {
        $this->defaultFilterChain = null;
    }

    /**
     * Set default validator chain to use
     *
     * @param  ValidatorChain $validatorChain
     * @return Factory
     */
    public function setDefaultValidatorChain(ValidatorChain $validatorChain)
    {
        $this->defaultValidatorChain = $validatorChain;
        return $this;
    }

    /**
     * Get default validator chain, if any
     *
     * @return null|ValidatorChain
     */
    public function getDefaultValidatorChain()
    {
        return $this->defaultValidatorChain;
    }

    /**
     * Clear the default validator chain (i.e., don't inject one into new inputs)
     *
     * @return void
     */
    public function clearDefaultValidatorChain()
    {
        $this->defaultValidatorChain = null;
    }

    /**
     * @param  InputFilterPluginManager $inputFilterManager
     * @return self
     */
    public function setInputFilterManager(InputFilterPluginManager $inputFilterManager)
    {
        $this->inputFilterManager = $inputFilterManager;
        $inputFilterManager->populateFactoryPluginManagers($this);
        return $this;
    }

    /**
     * @return InputFilterPluginManager
     */
    public function getInputFilterManager()
    {
        if (null === $this->inputFilterManager) {
            $this->inputFilterManager = new InputFilterPluginManager(new ServiceManager());
        }

        return $this->inputFilterManager;
    }

    /**
     * {@inheritdoc}
     */
    public function createInput($inputSpecification)
    {
        // Convert config
        $inputSpecification = $this->extractParam($inputSpecification);
        if ($inputSpecification instanceof  InputInterface) {
            return $inputSpecification;
        }
        // Recover inputFilter
        $input = $this->getInputFromConfig($inputSpecification, Input::class);

        if ($input instanceof InputFilterInterface) {
            return $this->createInputFilter($inputSpecification);
        }

        if ($this->defaultFilterChain) {
            $input->setFilterChain(clone $this->defaultFilterChain);
        }
        if ($this->defaultValidatorChain) {
            $input->setValidatorChain(clone $this->defaultValidatorChain);
        }

        foreach ($inputSpecification as $key => $value) {
            switch ($key) {
                case 'name':
                    $input->setName($value);
                    break;
                case 'required':
                    $input->setRequired($value);
                    break;
                case 'allow_empty':
                    $input->setAllowEmpty($value);
                    if (!isset($inputSpecification['required'])) {
                        $input->setRequired(!$value);
                    }
                    break;
                case 'continue_if_empty':
                    if (!$input instanceof Input) {
                        throw new Exception\RuntimeException(sprintf(
                            '%s "continue_if_empty" can only set to inputs of type "%s"',
                            __METHOD__,
                            Input::class
                        ));
                    }
                    $input->setContinueIfEmpty($inputSpecification['continue_if_empty']);
                    break;
                case 'error_message':
                    $input->setErrorMessage($value);
                    break;
                case 'fallback_value':
                    if (!$input instanceof Input) {
                        throw new Exception\RuntimeException(sprintf(
                            '%s "fallback_value" can only set to inputs of type "%s"',
                            __METHOD__,
                            Input::class
                        ));
                    }
                    $input->setFallbackValue($value);
                    break;
                case 'break_on_failure':
                    $input->setBreakOnFailure($value);
                    break;
                case 'filters':
                    if ($value instanceof FilterChain) {
                        $input->setFilterChain($value);
                        break;
                    }
                    if (!is_array($value) && !$value instanceof Traversable) {
                        throw new Exception\RuntimeException(sprintf(
                            '%s expects the value associated with "filters" to be an array/Traversable of filters or filter specifications, or a FilterChain; received "%s"',
                            __METHOD__,
                            (is_object($value) ? get_class($value) : gettype($value))
                        ));
                    }
                    $this->populateFilters($input->getFilterChain(), $value);
                    break;
                case 'validators':
                    if ($value instanceof ValidatorChain) {
                        $input->setValidatorChain($value);
                        break;
                    }
                    if (!is_array($value) && !$value instanceof Traversable) {
                        throw new Exception\RuntimeException(sprintf(
                            '%s expects the value associated with "validators" to be an array/Traversable of validators or validator specifications, or a ValidatorChain; received "%s"',
                            __METHOD__,
                            (is_object($value) ? get_class($value) : gettype($value))
                        ));
                    }
                    $this->populateValidators($input->getValidatorChain(), $value);
                    break;
                default:
                    // ignore unknown keys
                    break;
            }
        }

        return $input;
    }

    /**
     * Factory for input filters
     *
     * @param  array|Traversable|InputFilterProviderInterface $inputFilterSpecification
     * @throws Exception\InvalidArgumentException
     * @throws Exception\RuntimeException
     * @return InputFilterInterface
     */
    public function createInputFilter($inputFilterSpecification)
    {
        // Convert config
        $inputFilterSpecification = $this->extractParam($inputFilterSpecification);
        // Recover inputFilter
        $inputFilter = $this->getInputFromConfig($inputFilterSpecification);

        if ($inputFilter instanceof CollectionInputFilter) {
            $this->configCollectionInputFilter($inputFilter, $inputFilterSpecification);
        }

        foreach ($inputFilterSpecification as $key => $value) {

            if (null === $value || is_string($value) || is_scalar($value) || $key === 'input_filter') {
                continue;
            }

            $this->validateInput($value);

            $inputFilter->add(
                is_array($value) ? $this->createInput($value) : $value,
                is_array($value) ? (isset($value['name'])? $value['name'] : $key) : $key
            );
        }

        return $inputFilter;
    }

    /**
     * @param $specification
     * @return array
     */
    protected function extractParam($specification)
    {
        if ($specification instanceof  InputInterface
            || $specification instanceof InputFilterInterface
            || $specification instanceof CollectionInputFilter)
        {
            return $specification;
        }

        if ($specification instanceof InputFilterProviderInterface) {
            $specification = $specification->getInputFilterSpecification();
        }

        if ($specification instanceof InputProviderInterface) {
            $specification = $specification->getInputSpecification();
        }

        if (!is_array($specification) && !$specification instanceof Traversable) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array or Traversable; received "%s"',
                __METHOD__,
                (is_object($specification) ? get_class($specification) : gettype($specification))
            ));
        }

        if ($specification instanceof Traversable) {
            $specification = ArrayUtils::iteratorToArray($specification);
        }

        return $specification;
    }

    /**
     * @param array $config
     * @param string $default
     * @return \Zend\InputFilter\InputFilterInterface|\Zend\InputFilter\InputInterface
     */
    protected function getInputFromConfig(array $config, $default = InputFilter::class)
    {
        $inputFilter = $default;

        if (isset($config['type']) && is_string($config['type'])) {
            $inputFilter = $config['type'];
        }

        if (!$this->getInputFilterManager()->has($inputFilter)) {
            throw new Exception\RuntimeException(sprintf(
                'Input factory expects the "type" to be a valid class or a plugin name; received "%s"',
                $inputFilter
            ));
        }

        return $this->getInputFilterManager()->get($inputFilter);
    }

    /**
     * @param $value
     * @return bool
     */
    protected function validateInput($value)
    {
        if ($value instanceof InputInterface
            || $value instanceof InputFilterInterface
            || $value instanceof CollectionInputFilter)
        {
            return true;
        }

        if (!is_array($value)) {
            throw new Exception\InvalidArgumentException(sprintf(
                '%s expects an array; received "%s"',
                __METHOD__,
                (is_object($value) ? get_class($value) : gettype($value))
            ));
        }

        return true;
    }

    /**
     * @param CollectionInputFilter $inputFilterCollection
     * @param array $config
     * @return CollectionInputFilter
     */
    protected function configCollectionInputFilter(CollectionInputFilter $inputFilterCollection, array $config)
    {
        $inputFilterCollection->setFactory($this);
        if (isset($config['input_filter'])) {
            $inputFilterCollection->setInputFilter(is_array($config['input_filter']) ?
                    $this->createInputFilter($config['input_filter']) : $config['input_filter']
            );
        }
        if (isset($config['count'])) {
            $inputFilterCollection->setCount($config['count']);
        }
        if (isset($config['required'])) {
            $inputFilterCollection->setIsRequired($config['required']);
        }
        return $inputFilterCollection;
    }

    /**
     * @param  FilterChain       $chain
     * @param  array|Traversable $filters
     * @throws Exception\RuntimeException
     * @return void
     */
    protected function populateFilters(FilterChain $chain, $filters)
    {
        foreach ($filters as $filter) {
            if (is_object($filter) || is_callable($filter)) {
                $chain->attach($filter);
                continue;
            }

            if (is_array($filter)) {
                if (!isset($filter['name'])) {
                    throw new Exception\RuntimeException(
                        'Invalid filter specification provided; does not include "name" key'
                    );
                }
                $name = $filter['name'];
                $priority = isset($filter['priority']) ? $filter['priority'] : FilterChain::DEFAULT_PRIORITY;
                $options = [];
                if (isset($filter['options'])) {
                    $options = $filter['options'];
                }
                $chain->attachByName($name, $options, $priority);
                continue;
            }

            throw new Exception\RuntimeException(
                'Invalid filter specification provided; was neither a filter instance nor an array specification'
            );
        }
    }

    /**
     * @param  ValidatorChain    $chain
     * @param  string[]|ValidatorInterface[] $validators
     * @throws Exception\RuntimeException
     * @return void
     */
    protected function populateValidators(ValidatorChain $chain, $validators)
    {
        foreach ($validators as $validator) {
            if ($validator instanceof ValidatorInterface) {
                $chain->attach($validator);
                continue;
            }

            if (is_array($validator)) {
                if (!isset($validator['name'])) {
                    throw new Exception\RuntimeException(
                        'Invalid validator specification provided; does not include "name" key'
                    );
                }
                $name    = $validator['name'];
                $options = [];
                if (isset($validator['options'])) {
                    $options = $validator['options'];
                }
                $breakChainOnFailure = false;
                if (isset($validator['break_chain_on_failure'])) {
                    $breakChainOnFailure = $validator['break_chain_on_failure'];
                }
                $chain->attachByName($name, $options, $breakChainOnFailure);
                continue;
            }

            throw new Exception\RuntimeException(
                'Invalid validator specification provided; was neither a validator instance nor an array specification'
            );
        }
    }
}
