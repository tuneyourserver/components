<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Core\Traits\Config;

/**
 * Provides aliasing ability for config classes
 */
trait AliasTrait
{
    /**
     * @param string $alias
     * @return string
     */
    public function resolveAlias($alias)
    {
        while (is_string($alias) && isset($this->config['aliases'][$alias])) {
            //Resolving database alias
            $alias = $this->config['aliases'][$alias];
        }

        return $alias;

    }
}