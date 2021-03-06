<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Models\Exceptions;

use Spiral\Core\Exceptions\RuntimeException;

/**
 * Errors raised by Entity logic in runtime.
 */
class EntityException extends RuntimeException implements EntityExceptionInterface
{

}