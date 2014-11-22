<?php

/*
 * This file is part of the Ivory Http Adapter package.
 *
 * (c) Eric GELOEN <geloen.eric@gmail.com>
 *
 * For the full copyright and license information, please read the LICENSE
 * file that was distributed with this source code.
 */

namespace Ivory\HttpAdapter\Event\Subscriber;

use Ivory\HttpAdapter\Event\AbstractEvent;
use Ivory\HttpAdapter\Event\Events;
use Ivory\HttpAdapter\Event\ExceptionEvent;
use Ivory\HttpAdapter\Event\PreSendEvent;
use Ivory\HttpAdapter\Event\PostSendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Stopwatch subscriber.
 *
 * @author GeLo <geloen.eric@gmail.com>
 */
class StopwatchSubscriber implements EventSubscriberInterface
{
    /** @var \Symfony\Component\Stopwatch\Stopwatch */
    private $stopwatch;

    /**
     * Creates a stopwatch event subscriber.
     *
     * @param \Symfony\Component\Stopwatch\Stopwatch $stopwatch The stopwatch.
     */
    public function __construct(Stopwatch $stopwatch)
    {
        $this->setStopwatch($stopwatch);
    }

    /**
     * Gets the stopwatch.
     *
     * @return \Symfony\Component\Stopwatch\Stopwatch The stopwatch.
     */
    public function getStopwatch()
    {
        return $this->stopwatch;
    }

    /**
     * Sets the stopwatch.
     *
     * @param \Symfony\Component\Stopwatch\Stopwatch $stopwatch The stopwatch.
     */
    public function setStopwatch(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }

    /**
     * On pre send event.
     *
     * @param \Ivory\HttpAdapter\Event\PreSendEvent $event The event.
     */
    public function onPreSend(PreSendEvent $event)
    {
        $this->stopwatch->start($this->getStopwatchName($event));
    }

    /**
     * On post send event.
     *
     * @param \Ivory\HttpAdapter\Event\PostSendEvent $event The event.
     */
    public function onPostSend(PostSendEvent $event)
    {
        $this->stopwatch->stop($this->getStopwatchName($event));
    }

    /**
     * On exception event.
     *
     * @param \Ivory\HttpAdapter\Event\ExceptionEvent $event The event.
     */
    public function onException(ExceptionEvent $event)
    {
        $this->stopwatch->stop($this->getStopwatchName($event));
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            Events::PRE_SEND  => array('onPreSend', 10000),
            Events::POST_SEND => array('onPostSend', 10000),
            Events::EXCEPTION => array('onException', 10000),
        );
    }

    /**
     * Gets the stopwatch name.
     *
     * @param \Ivory\HttpAdapter\Event\AbstractEvent $event The event.
     *
     * @return string The stopwatch name.
     */
    private function getStopwatchName(AbstractEvent $event)
    {
        return sprintf(
            'ivory.http_adapter.%s (%s)',
            $event->getHttpAdapter()->getName(),
            (string) $event->getRequest()->getUrl()
        );
    }
}