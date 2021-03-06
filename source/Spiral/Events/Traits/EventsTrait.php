<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
namespace Spiral\Events\Traits;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Event trait utilized Symfony\Events dispatcher to add class (not instance) specific dispatcher.
 */
trait EventsTrait
{
    /**
     * @var EventDispatcherInterface[]
     */
    private static $dispatchers = [];

    /**
     * Set event dispatchers manually for current class. Can erase existed dispatcher by providing
     * null as value.
     *
     * @param EventDispatcherInterface|null $dispatcher
     */
    public static function setEvents(EventDispatcherInterface $dispatcher = null)
    {
        self::$dispatchers[static::class] = $dispatcher;
    }

    /**
     * Get class associated event dispatcher or create default one.
     *
     * @return EventDispatcherInterface
     */
    public static function events()
    {
        if (isset(self::$dispatchers[static::class])) {
            return self::$dispatchers[static::class];
        }

        return self::$dispatchers[static::class] = new EventDispatcher();
    }

    /**
     * Dispatch event. If no dispatched associated even will be returned without dispatching.
     *
     * @param string     $name  Event name.
     * @param Event|null $event Event class if any.
     * @return Event
     */
    protected function dispatch($name, Event $event = null)
    {
        if (empty(self::$dispatchers[static::class])) {
            //We can bypass dispatcher creation
            return $event;
        }

        return static::events()->dispatch($name, $event);
    }
}