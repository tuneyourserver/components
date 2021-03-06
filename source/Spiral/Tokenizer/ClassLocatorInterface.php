<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Tokenizer;

/**
 * Class locator interface.
 */
interface ClassLocatorInterface
{
    /**
     * Index all available files and generate list of found classes with their names and filenames.
     * Unreachable classes or files with conflicts must be skipped. This is SLOW method, should be
     * used only for static analysis.
     *
     * Output format:
     * $result['CLASS_NAME'] = [
     *      'class'    => 'CLASS_NAME',
     *      'filename' => 'FILENAME',
     *      'abstract' => 'ABSTRACT_BOOL'
     * ]
     *
     * @param mixed $target  Class, interface or trait parent. By default - null (all classes).
     *                       Parent (class) will also be included to classes list as one of
     *                       results.
     * @return array
     */
    public function getClasses($target = null);
}