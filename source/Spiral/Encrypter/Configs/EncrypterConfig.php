<?php
/**
 * Spiral Framework.
 *
 * @license MIT
 * @author  Anton Titov (Wolfy-J)
 */
namespace Spiral\Encrypter\Configs;

use Spiral\Core\InjectableConfig;

/**
 * Encrypter configuration.
 */
class EncrypterConfig extends InjectableConfig
{
    /**
     * Configuration section.
     */
    const CONFIG = 'encrypter';

    /**
     * Default algorythm.
     */
    const DEFAULT_CIPHER = 'aes-256-cbc';

    /**
     * @var array
     */
    protected $config = [
        'key'    => '',
        'cipher' => ''
    ];

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->config['key'];
    }

    /**
     * @return string
     */
    public function getCipher()
    {
        if (empty($this->config['cipher'])) {
            return static::DEFAULT_CIPHER;
        }

        return $this->config['cipher'];
    }
}