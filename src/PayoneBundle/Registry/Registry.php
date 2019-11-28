<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace PayoneBundle\Registry;


use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Pimcore\Bundle\NumberSequenceGeneratorBundle\RandomGenerator;
use Pimcore\Db;
use Pimcore\Model\Tool\Lock;

/**
 * Class Registry
 * @package PayoneBundle\Registry
 */
class Registry implements IRegistry
{
    const TABLE_NAME = "bundle_payone_registry";
    const REFERENCE_LENGTH = 14;
    const GENERATOR_RANGE = "payone_reference";
    const LOCK_KEY = 'payony_reference_registry';

    const COLUMN_INTERNAL_REFERENCE = "internal_payment_id";
    const COLUMN__PAYONE_REFERENCE = "payone_reference";

    const LOG_TABLE_NAME = "bundle_payone_transaction_log";
    const COLUMN_DATA = "data";
    const COLUMN_TYPE = "type";
    const COLUMN_TIMESTAMP = "timestamp";
    const COLUMN_TXID = "txid";
    const COLUMN_METHOD = "method";

    const LOG_LOCK_KEY = 'payony_transaction_log';

    /**
     * @var RandomGenerator
     */
    private $generator;
    /**
     * Registry constructor.
     * @param RandomGenerator $generator
     */
    public function __construct(RandomGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @param $payoneReference
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Exception
     */
    public function getInternalByExternalReference($payoneReference)
    {

        $db = Db::get();
        $result = $db->fetchRow(
            "SELECT * FROM " . self::TABLE_NAME . " WHERE `" . self::COLUMN__PAYONE_REFERENCE . "` = ?",
            [$payoneReference]
        );
        if (!$result) {
            throw new \Exception('reference does not exist');
        }


        return $result['internal_payment_id'];
    }

    /**
     * @param $internalReference
     * @return string
     * @throws \Exception
     */
    public function generateAndStoreExternalReference($internalReference)
    {
        Lock::acquire(self::LOCK_KEY);

        $payone_reference = $this->generator->generateCode(self::GENERATOR_RANGE, \Pimcore\Bundle\NumberSequenceGeneratorBundle\RandomGenerator::ALPHANUMERIC, self::REFERENCE_LENGTH);

        $db = Db::get();
        $db->insert(self::TABLE_NAME, [self::COLUMN_INTERNAL_REFERENCE => $internalReference, self::COLUMN__PAYONE_REFERENCE => $payone_reference]);

        Lock::release(self::LOCK_KEY);

        return $payone_reference;

    }

    /**
     * @param $reference
     * @param $txId
     * @param $type
     * @param $data
     */
    public function logTransaction($reference, $txId, $type, $data)
    {
        Lock::acquire(self::LOG_LOCK_KEY);

        $now = CarbonImmutable::now();

        $db = Db::get();
        $db->insert(self::LOG_TABLE_NAME, [
            self::COLUMN_TYPE => $type,
            self::COLUMN_TIMESTAMP => $now,
            self::COLUMN_METHOD => $data['_method'],
            self::COLUMN__PAYONE_REFERENCE => $reference,
            self::COLUMN_TXID => $txId,
            self::COLUMN_DATA => json_encode($data),
        ]);

        Lock::release(self::LOG_LOCK_KEY);

    }

    /**
     * @param $txid
     * @return array|null
     */
    public function findTransactionLogsForTXid($txid)
    {

    }

    /**
     * @param $internalReference
     * @return array|null
     */
    public function findTransactionLogsForInternalId($internalReference)
    {

    }

    /**
     * @param $payoneReference
     * @return array|null
     */
    public function findTranslationLogsForPayoneReference($payoneReference)
    {

    }


}
