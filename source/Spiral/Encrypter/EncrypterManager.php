<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Encrypter;

use Spiral\Core\Container\InjectorInterface;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Core\FactoryInterface;
use Spiral\Encrypter\Configs\EncrypterConfig;

/**
 * Only manages encrypter injections (factory).
 */
class EncrypterManager implements InjectorInterface, SingletonInterface
{
    /**
     * To be constructed only once.
     */
    const SINGLETON = self::class;

    /**
     * @var FactoryInterface
     */
    protected $factory = null;

    /**
     * @var EncrypterConfig
     */
    protected $config = null;

    /**
     * @param EncrypterConfig  $config
     * @param FactoryInterface $factory
     */
    public function __construct(EncrypterConfig $config, FactoryInterface $factory)
    {
        $this->factory = $factory;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function createInjection(\ReflectionClass $class, $context = null)
    {
        return $this->factory->make($class->getName(), [
            'key'    => $this->config->getKey(),
            'cipher' => $this->config->getCipher()
        ]);
    }
}