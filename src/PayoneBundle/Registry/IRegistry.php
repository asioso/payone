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

/**
 * Interface IRegistry
 * @package PayoneBundle\Registry
 */
interface IRegistry
{
    /**
     * @param $payoneReference
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public static function getInternalByExternalReference($payoneReference);

    /**
     * @param $internalReference
     * @return string
     * @throws \Exception
     */
    public static function generateAndStoreExternalReference($internalReference);

    /**
     * @param $reference
     * @param $txId
     * @param $type
     * @param $data
     */
    public static function logTransaction($reference, $txId, $type ,$data);


    /**
     * @param $txid
     * @return array|null
     */
    public static function findTransactionLogsForTXid($txid);

    /**
     * @param $internalReference
     * @return array|null
     */
    public static function findTransactionLogsForInternalId($internalReference);

    /**
     * @param $payoneReference
     * @return array|null
     */
    public static function findTranslationLogsForPayoneReference($payoneReference);

}
