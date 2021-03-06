<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace PayoneBundle\Registry;


use PayoneBundle\Ecommerce\PaymentManager\BsPayone;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\PaymentManagerInterface;

interface CaptureQueueInterface
{

    /**
     * @param string $txid
     * @param string $reference
     * @param string $amount
     * @param string $currency
     * @param array $options
     */
    public function addCapture(string $txid,string  $reference,string  $amount,string  $currency, array $options): void;

    /**
     * @param $txid
     * @param BsPayone $payone
     * @return mixed
     */
    public function resolveCapture($txid, BsPayone $payone);

}