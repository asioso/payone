<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace PayoneBundle\Service;


use GuzzleHttp\Psr7\Stream;
use PayoneBundle\Ecommerce\PaymentManager\Helper\Payone;
use PayoneBundle\Event\PayoneEvents;
use PayoneBundle\Event\PayoneRequestEvent;
use PayoneBundle\Event\PayoneResponseEvent;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class ServerToServerHandler implements ServerToServerServiceInterface
{

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ServerToServerHandler constructor.
     * @param EventDispatcherInterface $eventDispatcher
     * @param LoggerInterface $logger
     */
    public function __construct(EventDispatcherInterface $eventDispatcher,LoggerInterface $logger)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->logger = $logger;
    }

    /**
     * @param $params
     * @return array|\Psr\Http\Message\StreamInterface
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function serverToServerRequest($params)
    {
        $this->eventDispatcher->dispatch(new PayoneRequestEvent($params), PayoneEvents::PAYONE_PRE_SEND_REQUEST_EVENT);
        $response = Payone::sendRequest($params, "", $this->logger);

        if ($response instanceof Stream) {
            $response = Payone::parseResponse((string)$response->getContents(), $this->logger); // returns all the contents
        }

        $this->eventDispatcher->dispatch(new PayoneResponseEvent($params, $response), PayoneEvents::PAYONE_POST_SEND_REQUEST_EVENT);

        return $response;
    }
}