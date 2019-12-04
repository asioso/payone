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
use PayoneBundle\Ecommerce\PayoneCommitOrderProcessorInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Pimcore\Event\Ecommerce\OrderAgentEvents;
use Pimcore\Event\Model\Ecommerce\OrderAgentEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class PaymentUpdateSubscriber implements EventSubscriberInterface
{

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return array(
            OrderAgentEvents::POST_UPDATE_PAYMENT => "onPayoneOrderUpdate"
        );
    }

    public function onPayoneOrderUpdate(OrderAgentEvent $event )
    {
        $agent = $event->getOrderAgent();
        if($agent->getPaymentProvider() instanceof BsPayone){
            /**
             * @var $paymentStatus StatusInterface
             */
            $paymentStatus = $event->getArgument('status');
            $processor = \Pimcore\Bundle\EcommerceFrameworkBundle\Factory::getInstance()->getCommitOrderProcessor();

            if($paymentStatus->getStatus() == StatusInterface::STATUS_AUTHORIZED){
                if($processor instanceof PayoneCommitOrderProcessorInterface){
                    $processor->processOrderOnAuthorized($agent->getOrder());
                }
            }else if($paymentStatus->getStatus() == StatusInterface::STATUS_CLEARED){
                if($processor instanceof PayoneCommitOrderProcessorInterface){
                    $processor->processOrderOnPaid($agent->getOrder());
                }
            }

        }


    }
}