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
    public function getInternalByExternalReference($payoneReference);

    /**
     * @param $internalReference
     * @return string
     * @throws \Exception
     */
    public function generateAndStoreExternalReference($internalReference);


}