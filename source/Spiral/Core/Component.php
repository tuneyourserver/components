<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Core;

use Interop\Container\ContainerInterface;

/**
 * Basic spiral cell. Automatically detects if "container" property are presented in class or uses
 * global container as fallback.
 */
abstract class Component
{
    /**
     * @var ContainerInterface
     */
    private $container = null;

    /**
     * Global/static mainly used to resolve singletons outside of the runtime scope.
     * Must be used as fallback only, or not used at all. All spiral components can
     * behave well without it.
     *
     * @var ContainerInterface
     */
    private static $staticContainer = null;

    /**
     * Optional constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Get/set instance of instance specific container.
     *
     * @param ContainerInterface $container Can be set to null.
     */
    protected function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * Get instance of container associated with given object, uses global container as fallback
     * if not. Method generally used by traits.
     *
     * @return ContainerInterface|null
     */
    protected function container()
    {
        if ($this->container instanceof ContainerInterface) {
            return $this->container;
        }

        return self::$staticContainer;
    }

    /**
     * Get/set instance of global/static container, due this method must be used as few times as
     * possible both getter and setter methods joined into one. Please use this method only as
     * fallback.
     *
     * @internal Do not use for business logic.
     * @param ContainerInterface $container Can be set to null.
     * @return ContainerInterface|null
     */
    final protected static function staticContainer(ContainerInterface $container = null)
    {
        if (func_num_args() === 0) {
            return self::$staticContainer;
        }

        $outer = self::$staticContainer;

        self::$staticContainer = $container;

        //Return previous container or null
        return $outer;
    }
}
