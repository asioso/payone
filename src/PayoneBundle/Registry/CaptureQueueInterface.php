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


interface CaptureQueueInterface
{

    /**
     * @param $txid
     * @param $reference
     * @param $amount
     * @param $currency
     * @param array $options
     */
    public function addCapture($txid, $reference, $amount, $currency, array $options): void;

    /**
     * @param $txid
     * @return mixed
     */
    public function resolveCapture($txid);

}