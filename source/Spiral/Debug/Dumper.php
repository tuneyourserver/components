<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Debug;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Spiral\Core\Component;
use Spiral\Core\Container\SingletonInterface;
use Spiral\Debug\Dumper\Style;
use Spiral\Debug\Traits\BenchmarkTrait;
use Spiral\Debug\Traits\LoggerTrait;

/**
 * One of the oldest spiral parts, used to dump variables content in user friendly way.
 */
class Dumper extends Component implements SingletonInterface, LoggerAwareInterface
{
    use LoggerTrait, BenchmarkTrait;

    /**
     * Declaring to IoC that class is singleton.
     */
    const SINGLETON = self::class;

    /**
     * Options for dump() function to specify output.
     */
    const OUTPUT_ECHO     = 0;
    const OUTPUT_RETURN   = 1;
    const OUTPUT_LOG      = 2;
    const OUTPUT_LOG_NICE = 3;

    /**
     * Deepest level to be dumped.
     *
     * @var int
     */
    private $maxLevel = 10;

    /**
     * @invisible
     * @var Style
     */
    private $style = null;

    /**
     * @param int             $maxLevel
     * @param Style           $styler Light styler to be used by default.
     * @param LoggerInterface $logger
     */
    public function __construct(
        $maxLevel = 10,
        Style $styler = null,
        LoggerInterface $logger = null
    ) {
        $this->maxLevel = $maxLevel;
        $this->style = !empty($styler) ? $styler : new Style();
        $this->logger = $logger;
    }

    /**
     * Set dump styler.
     *
     * @param Style $style
     * @return $this
     */
    public function setStyle(Style $style)
    {
        $this->style = $style;

        return $this;
    }

    /**
     * Dump specified value.
     *
     * @param mixed $value
     * @param int   $output
     * @return null|string
     */
    public function dump($value, $output = self::OUTPUT_ECHO)
    {
        if (php_sapi_name() === 'cli' && $output == self::OUTPUT_ECHO) {
            print_r($value);
            if (is_scalar($value)) {
                echo "\n";
            }

            return null;
        }

        //Dumping is pretty slow operation, let's record it so we can exclude dump time from application
        //timeline
        $benchmark = $this->benchmark('dump');
        try {
            switch ($output) {
                case self::OUTPUT_ECHO:
                    echo $this->style->mountContainer($this->dumpValue($value, '', 0));
                    break;

                case self::OUTPUT_RETURN:
                    return $this->style->mountContainer($this->dumpValue($value, '', 0));
                    break;

                case self::OUTPUT_LOG:
                    $this->logger()->debug(print_r($value, true));
                    break;

                case self::OUTPUT_LOG_NICE:
                    $this->logger()->debug($this->dump($value, self::OUTPUT_RETURN));
                    break;
            }

            return null;
        } finally {
            $this->benchmark($benchmark);
        }
    }

    /**
     * Variable dumper. This is the oldest spiral function originally written in 2007. :)
     *
     * @param mixed  $value
     * @param string $name       Variable name, internal.
     * @param int    $level      Dumping level, internal.
     * @param bool   $hideHeader Hide array/object header, internal.
     * @return string
     */
    private function dumpValue($value, $name = '', $level = 0, $hideHeader = false)
    {
        //Any dump starts with initial indent (level based)
        $indent = $this->style->indent($level);

        if (!$hideHeader && !empty($name)) {
            //Showing element name (if any provided)
            $header = $indent . $this->style->style($name, "name");

            //Showing equal sing
            $header .= $this->style->style(" = ", "syntax", "=");
        } else {
            $header = $indent;
        }

        if ($level > $this->maxLevel) {
            //Dumper is not reference based, we can't dump too deep values
            return $indent . $this->style->style('-too deep-', 'maxLevel') . "\n";
        }

        $type = strtolower(gettype($value));

        if ($type == 'array') {
            return $header . $this->dumpArray($value, $level, $hideHeader);
        }

        if ($type == 'object') {
            return $header . $this->dumpObject($value, $level, $hideHeader);
        }

        if ($type == 'resource') {
            //No need to dump resource value
            $element = get_resource_type($value) . " resource ";

            return $header . $this->style->style($element, "type", "resource") . "\n";
        }

        //Value length
        $length = strlen($value);

        //Including type size
        $header .= $this->style->style("{$type}({$length})", "type", $type);

        $element = null;
        switch ($type) {
            case "string":
                $element = htmlspecialchars($value);
                break;

            case "boolean":
                $element = ($value ? "true" : "false");
                break;

            default:
                if ($value !== null) {
                    //Not showing null value, type is enough
                    $element = var_export($value, true);
                }
        }

        //Including value
        return $header . " " . $this->style->style($element, "value", $type) . "\n";
    }

    /**
     * @param array $array
     * @param int   $level
     * @param bool  $hideHeader
     * @return string
     */
    private function dumpArray(array $array, $level, $hideHeader = false)
    {
        $indent = $this->style->indent($level);

        if (!$hideHeader) {
            $count = count($array);

            //Array size and scope
            $output = $this->style->style("array({$count})", "type", "array") . "\n";
            $output .= $indent . $this->style->style("[", "syntax", "[") . "\n";
        } else {
            $output = '';
        }

        foreach ($array as $key => $value) {
            if (!is_numeric($key)) {
                if (is_string($key)) {
                    $key = htmlspecialchars($key);
                }

                $key = "'{$key}'";
            }

            $output .= $this->dumpValue($value, "[{$key}]", $level + 1);
        }

        if (!$hideHeader) {
            //Closing array scope
            $output .= $indent . $this->style->style("]", "syntax", "]") . "\n";
        }

        return $output;
    }

    /**
     * @param object $object
     * @param int    $level
     * @param bool   $hideHeader
     * @param string $class
     * @return string
     */
    private function dumpObject($object, $level, $hideHeader = false, $class = '')
    {
        $indent = $this->style->indent($level);

        if (!$hideHeader) {
            $type = ($class ?: get_class($object)) . " object ";

            $header = $this->style->style($type, "type", "object") . "\n";
            $header .= $indent . $this->style->style("(", "syntax", "(") . "\n";
        } else {
            $header = '';
        }

        //Let's use method specifically created for dumping
        if (method_exists($object, '__debugInfo')) {
            $debugInfo = $object->__debugInfo();

            if (is_object($debugInfo)) {
                //We are not including syntax elements here
                return $this->dumpObject($debugInfo, $level, false, get_class($object));
            }

            return $header
            . $this->dumpValue($debugInfo, '', $level + (is_scalar($object)), true)
            . $indent . $this->style->style(")", "syntax", ")") . "\n";
        }

        $refection = new \ReflectionObject($object);

        $output = '';
        foreach ($refection->getProperties() as $property) {
            $output .= $this->dumpProperty($object, $property, $level);
        }

        //Header, content, footer
        return $header . $output . $indent . $this->style->style(")", "syntax", ")") . "\n";
    }

    /**
     * @param object              $object
     * @param \ReflectionProperty $property
     * @param int                 $level
     * @return string
     */
    private function dumpProperty($object, \ReflectionProperty $property, $level)
    {
        if ($property->isStatic()) {
            return '';
        }

        if (
            !($object instanceof \stdClass)
            && strpos($property->getDocComment(), '@invisible') !== false
        ) {
            //Memory loop while reading doc comment for stdClass variables?
            //Report a PHP bug about treating comment INSIDE property declaration as doc comment.
            return '';
        }

        //Property access level
        $access = $this->getAccess($property);

        //To read private and protected properties
        $property->setAccessible(true);

        if ($object instanceof \stdClass) {
            $access = 'dynamic';
        }

        //Property name includes access level
        $name = $property->getName() . $this->style->style(":" . $access, "access", $access);

        return $this->dumpValue($property->getValue($object), $name, $level + 1);
    }

    /**
     * Property access level label.
     *
     * @param \ReflectionProperty $property
     * @return string
     */
    private function getAccess(\ReflectionProperty $property)
    {
        if ($property->isPrivate()) {
            return 'private';
        } elseif ($property->isProtected()) {
            return 'protected';
        }

        return 'public';
    }
}