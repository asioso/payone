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
            "SELECT * FROM ".self::TABLE_NAME." WHERE `".self::COLUMN__PAYONE_REFERENCE."` = ?",
            [$payoneReference]
        );
        if(!$result){
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

        $generator = \Pimcore::getContainer()->get(\Pimcore\Bundle\NumberSequenceGeneratorBundle\RandomGenerator::class);
        $payone_reference = $generator->generateCode(self::GENERATOR_RANGE, \Pimcore\Bundle\NumberSequenceGeneratorBundle\RandomGenerator::ALPHANUMERIC, self::REFERENCE_LENGTH);

        $db = Db::get();
        $db->insert(self::TABLE_NAME, [self::COLUMN_INTERNAL_REFERENCE => $internalReference, self::COLUMN__PAYONE_REFERENCE => $payone_reference]);

        Lock::release(self::LOCK_KEY);

        return $payone_reference;

    }
}