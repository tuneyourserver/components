<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Tokenizer;

use Spiral\Tokenizer\Reflections\ReflectionInvocation;

/**
 * Analog of LocatorInterface for method/function invocations. Can only work with simple invocations
 * such as $this->method, self::method, static::method, or ClassName::method.
 *
 * @todo use AST
 */
interface InvocationLocatorInterface
{
    /**
     * Find all possible invocations of given function or method. Make sure you know about location
     * limitations.
     *
     * @param \ReflectionFunctionAbstract $function
     * @return ReflectionInvocation[]
     */
    public function getInvocations(\ReflectionFunctionAbstract $function);
}