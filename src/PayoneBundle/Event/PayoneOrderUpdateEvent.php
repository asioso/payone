<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace PayoneBundle\Event;


use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractOrder;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\StatusInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PayoneOrderUpdateEvent extends Event
{
    /**
     * @var StatusInterface
     */
    private $status;
    /**
     * @var AbstractOrder
     */
    private $oder;

    public function __construct(StatusInterface $status, AbstractOrder $oder)
    {

        $this->status = $status;
        $this->oder = $oder;
    }

    /**
     * @return StatusInterface
     */
    public function getStatus(): StatusInterface
    {
        return $this->status;
    }

    /**
     * @return AbstractOrder
     */
    public function getOder(): AbstractOrder
    {
        return $this->oder;
    }





}