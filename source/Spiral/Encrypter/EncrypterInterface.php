<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 * @copyright ©2009-2015
 */
namespace Spiral\Encrypter;

use Spiral\Encrypter\Exceptions\DecryptException;
use Spiral\Encrypter\Exceptions\EncrypterException;
use Spiral\Encrypter\Exceptions\EncryptException;

/**
 * Encryption services.
 */
interface EncrypterInterface
{
    /**
     * Update encryption key.
     *
     * @param string $key
     * @return self
     * @throws EncrypterException
     */
    public function setKey($key);

    /**
     * Encryption ket value. Sensitive data!
     *
     * @return string
     */
    public function getKey();

    /**
     * Generate random string.
     *
     * @param int $length
     * @return string
     * @throws EncrypterException
     */
    public function random($length);

    /**
     * Encrypt data into encrypter specific payload string. Can be decrypted only using decrypt()
     * method.
     *
     * @see decrypt()
     * @param mixed $data
     * @return string
     * @throws EncryptException
     * @throws EncrypterException
     */
    public function encrypt($data);

    /**
     * Decrypt payload string. Payload should be generated by same encrypter using encrypt() method.
     *
     * @see encrypt()
     * @param string $payload
     * @return mixed
     * @throws DecryptException
     * @throws EncrypterException
     */
    public function decrypt($payload);
}