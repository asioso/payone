<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace PayoneBundle\EventListener;


use PayoneBundle\Ecommerce\PaymentManager\BsPayone;
use PayoneBundle\Event\PayoneEvents;
use PayoneBundle\Event\PayoneResponseEvent;
use PayoneBundle\Registry\CaptureQueueInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CaptureListener implements EventSubscriberInterface
{
    /**
     * @var CaptureQueueInterface
     */
    private $captureQueue;

    /**
     * CaptureListener constructor.
     * @param CaptureQueueInterface $captureQueue
     */
    public function __construct(CaptureQueueInterface $captureQueue)
    {
        $this->captureQueue = $captureQueue;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * ['eventName' => 'methodName']
     *  * ['eventName' => ['methodName', $priority]]
     *  * ['eventName' => [['methodName1', $priority], ['methodName2']]]
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            PayoneEvents::PAYONE_POST_SEND_REQUEST_EVENT => ['onPayoneResponse', 99]
        ];
    }

    public function onPayoneResponse(PayoneResponseEvent $event)
    {
        if($event->getRequestParameters()['request'] == 'preauthorization'){
            $this->captureQueue->addCapture(
                $event->getResponseParameters()['txid'],
                $event->getRequestParameters()['reference'],
                $event->getRequestParameters()['amount'],
                $event->getRequestParameters()['currency'],
                $event->getRequestParameters()
                );

            //do not resolve now.
            //$this->captureQueue->resolveCapture($event->getResponseParameters()['txid']);

        }

    }
}