<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Validation;

use Interop\Container\ContainerInterface;
use Psr\Log\LoggerAwareInterface;
use Spiral\Core\Component;
use Spiral\Core\Exceptions\SugarException;
use Spiral\Core\Traits\SaturateTrait;
use Spiral\Debug\Traits\LoggerTrait;
use Spiral\Translator\Traits\TranslatorTrait;
use Spiral\Validation\Configs\ValidatorConfig;
use Spiral\Validation\Exceptions\ValidationException;

/**
 * Validator is default implementation of ValidatorInterface. Class support functional rules with
 * user parameters. In addition, part of validation rules moved into validation checkers used to
 * simplify adding new rules, checkers are resolved using container and can be rebinded in
 * application.
 *
 * Examples:
 *
 * "status" => [
 *      ["notEmpty"],
 *      ["string::shorter", 10, "error" => "Your string is too short."],
 *      [["MyClass","myMethod"], "error" => "Custom validation failed."]
 * [,
 * "email" => [
 *      ["notEmpty", "error" => "Please enter your email address."],
 *      ["email", "error" => "Email is not valid."]
 * [,
 * "pin" => [
 *      ["string::regexp", "/[0-9]{5}/", "error" => "Invalid pin format, if you don't know your
 *                                                   pin, please skip this field."]
 * [,
 * "flag" => ["notEmpty", "boolean"]
 *
 * In cases where you don't need custom message or check parameters you can use simplified
 * rule syntax:
 * "flag" => ["notEmpty", "boolean"]
 */
class Validator extends Component implements ValidatorInterface, LoggerAwareInterface
{
    /**
     * Validator will translate default errors and throw log messages when validation rule fails.
     */
    use LoggerTrait, TranslatorTrait, SaturateTrait;

    /**
     * Return from validation rule to stop any future field validations. Internal contract.
     */
    const STOP_VALIDATION = -99;

    /**
     * @var array|\ArrayAccess
     */
    private $data = [];

    /**
     * Validation rules, see class title for description.
     *
     * @var array
     */
    private $rules = [];

    /**
     * Error messages raised while validation.
     *
     * @var array
     */
    private $errors = [];

    /**
     * Errors provided from outside.
     *
     * @var array
     */
    private $registeredErrors = [];

    /**
     * If rule has no definer error message this text will be used instead. Localizable.
     *
     * @invisible
     * @var string
     */
    protected $defaultMessage = "[[Condition '{condition}' does not meet.]]";

    /**
     * @invisible
     * @var ContainerInterface
     */
    protected $container = null;

    /**
     * @invisible
     * @var ValidatorConfig
     */
    protected $config = null;

    /**
     * {@inheritdoc}
     *
     * @param ValidatorConfig    $config
     * @param ContainerInterface $container
     * @throws SugarException
     */
    public function __construct(
        array $rules = [],
        $data = [],
        ValidatorConfig $config = null,
        ContainerInterface $container = null
    ) {
        $this->data = $data;
        $this->rules = $rules;

        //Let's get validation from shared container if none provided
        $this->config = $this->saturate($config, ValidatorConfig::class);

        //We can use global container as fallback if no default values were provided
        $this->container = $this->saturate($container, ContainerInterface::class);
    }

    /**
     * {@inheritdoc}
     */
    public function setRules(array $rules)
    {
        if ($this->rules == $rules) {
            return $this;
        }

        $this->rules = $rules;
        $this->errors = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setData($data)
    {
        if ($this->data == $data) {
            return $this;
        }

        $this->data = $data;
        $this->errors = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function isValid()
    {
        $this->validate();

        return empty($this->errors) && empty($this->registeredErrors);
    }

    /**
     * {@inheritdoc}
     */
    public function registerError($field, $error)
    {
        $this->registeredErrors[$field] = $error;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flushRegistered()
    {
        $this->registeredErrors = [];

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasErrors()
    {
        return !$this->isValid();
    }

    /**
     * {@inheritdoc}
     */
    public function getErrors()
    {
        $this->validate();

        return $this->registeredErrors + $this->errors;
    }

    /**
     * Receive field from context data or return default value.
     *
     * @param string $field
     * @param mixed  $default
     * @return mixed
     */
    public function field($field, $default = null)
    {
        $value = isset($this->data[$field]) ? $this->data[$field] : $default;

        return $value instanceof ValueInterface ? $value->serializeData() : $value;
    }

    /**
     * Validate context data with set of validation rules.
     */
    protected function validate()
    {
        $this->errors = [];
        foreach ($this->rules as $field => $rules) {
            foreach ($rules as $rule) {
                if (isset($this->errors[$field])) {
                    //We are validating field till first error
                    continue;
                }

                //Condition either rule itself or first array element
                $condition = is_string($rule) ? $rule : $rule[0];

                if (empty($this->field($field)) && !$this->config->emptyCondition($condition)) {
                    //There is no need to validate empty field except for special conditions
                    break;
                }

                $result = $this->check(
                    $field,
                    $this->field($field),
                    $condition,
                    $arguments = is_string($rule) ? [] : $this->fetchArguments($rule)
                );

                if ($result === true) {
                    //No errors
                    continue;
                }

                if ($result === self::STOP_VALIDATION) {
                    //Validation has to be stopped per rule request
                    break;
                }

                if ($result instanceof Checker) {
                    //Failed inside checker, this is implementation agreement
                    if ($message = $result->getMessage($condition[1])) {
                        //Checker provides it's own message for condition
                        $this->addMessage(
                            $field,
                            is_string($rule) ? $message : $this->fetchMessage($rule, $message),
                            $condition,
                            $arguments
                        );

                        continue;
                    }
                }

                //Default message
                $message = $this->say($this->defaultMessage);

                //Recording error message
                $this->addMessage(
                    $field,
                    is_string($rule) ? $message : $this->fetchMessage($rule, $message),
                    $condition,
                    $arguments
                );
            }
        }
    }

    /**
     * Check field with given condition. Can return instance of Checker (data is not valid) to
     * clarify error.
     *
     * @param string $field
     * @param mixed  $value
     * @param mixed  $condition Reference, can be altered if alias exists.
     * @param array  $arguments Rule arguments if any.
     * @return bool|Checker
     * @throws ValidationException
     */
    protected function check($field, $value, &$condition, array $arguments = [])
    {
        $condition = $this->config->resolveAlias($condition);

        try {
            if (strpos($condition, '::')) {
                $condition = explode('::', $condition);
                if ($this->config->hasChecker($condition[0])) {
                    $checker = $this->checker($condition[0]);
                    if (!$result = $checker->check($condition[1], $value, $arguments, $this)) {
                        //To let validation() method know that message should be handled via Checker
                        return $checker;
                    }

                    return $result;
                }
            }

            if (is_array($condition)) {
                //We are going to resolve class using constructor
                $condition[0] = is_object($condition[0])
                    ? $condition[0]
                    : $this->container->get($condition[0]);
            }

            //Value always coming first
            array_unshift($arguments, $value);

            return call_user_func_array($condition, $arguments);
        } catch (\ErrorException $exception) {
            $condition = func_get_arg(2);
            if (is_array($condition)) {
                if (is_object($condition[0])) {
                    $condition[0] = get_class($condition[0]);
                }

                $condition = join('::', $condition);
            }

            $this->logger()->error(
                "Condition '{condition}' failed with '{exception}' while checking '{field}' field.",
                compact('condition', 'field') + ['exception' => $exception->getMessage()]
            );

            return false;
        }
    }

    /**
     * Get or create instance of validation checker.
     *
     * @param string $name
     * @return Checker
     * @throws ValidationException
     */
    protected function checker($name)
    {
        if (!$this->config->hasChecker($name)) {
            throw new ValidationException(
                "Unable to create validation checker defined by '{$name}' name."
            );
        }

        return $this->container->get($this->config->checkerClass($name));
    }

    /**
     * Fetch validation rule arguments from rule definition.
     *
     * @param array $rule
     * @return array
     */
    private function fetchArguments(array $rule)
    {
        unset($rule[0], $rule['message'], $rule['error']);

        return array_values($rule);
    }

    /**
     * Fetch error message from rule definition or use default message. Method will check "message"
     * and "error" properties of definition.
     *
     * @param array  $rule
     * @param string $message Default message to use.
     * @return mixed
     */
    private function fetchMessage(array $rule, $message)
    {
        if (isset($rule['message'])) {
            $message = $rule['message'];
        }

        if (isset($rule['error'])) {
            $message = $rule['error'];
        }

        return $message;
    }

    /**
     * Register error message for specified field. Rule definition will be interpolated into
     * message.
     *
     * @param string $field
     * @param string $message
     * @param mixed  $condition
     * @param array  $arguments
     */
    private function addMessage($field, $message, $condition, array $arguments = [])
    {
        if (is_array($condition)) {
            if (is_object($condition[0])) {
                $condition[0] = get_class($condition[0]);
            }

            $condition = join('::', $condition);
        }

        $this->errors[$field] = \Spiral\interpolate(
            $message,
            compact('field', 'condition') + $arguments
        );
    }
}