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
use PayoneBundle\Ecommerce\PaymentManager\BsPayone;
use PayoneBundle\Service\ServerToServerServiceInterface;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Db;
use Pimcore\Model\Tool\Lock;

class CaptureHandler implements CaptureQueueInterface
{
    const LOG_TABLE_NAME = "bundle_payone_capture_log";
    const COLUMN_TIMESTAMP = "timestamp";
    const COLUMN_PAYONE_REFERENCE = "payone_reference";
    const COLUMN_TXID = "txid";
    const COLUMN_AMOUNT = "amount";
    const COLUMN_CURRENCY = "currency";
    const COLUMN_PROCESSED = "processed";
    const COLUMN_DATA = "data";

    const LOG_LOCK_KEY = 'payone_capture_log';

    /**
     * @var ServerToServerServiceInterface
     */
    private $serverService;
    /**
     * @var BsPayone
     */
    private $payone;

    public function __construct( ServerToServerServiceInterface $serverService)
    {
        $this->serverService = $serverService;
        $this->payone = Factory::getInstance()->getPaymentManager()->getProvider('payone');
    }

    /**
     * @param $txid
     * @param $reference
     * @param $amount
     * @param $currency
     * @param array $options
     */
    public function addCapture($txid, $reference, $amount, $currency, array $options): void
    {

        Lock::acquire(self::LOG_LOCK_KEY);

        $now = CarbonImmutable::now();

        $db = Db::get();
        $db->insert(self::LOG_TABLE_NAME, [
            self::COLUMN_TIMESTAMP => $now,
            self::COLUMN_AMOUNT => $amount,
            self::COLUMN_CURRENCY => $currency,
            self::COLUMN_TXID => $txid,
            self::COLUMN_PAYONE_REFERENCE => $reference,
            self::COLUMN_PROCESSED => null,
            self::COLUMN_DATA => json_encode($options),

        ]);

        Lock::release(self::LOG_LOCK_KEY);

    }

    /**
     * @param $txid
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function resolveCapture($txid)
    {
        $db = Db::get();
        $result = $db->fetchRow(
            "SELECT * FROM " . self::LOG_TABLE_NAME . " WHERE `" . self::COLUMN_TXID . "` = ?",
            [$txid]
        );

        if (!$result) {
            throw new \Exception('reference does not exist');
        } elseif ($result['processed'] != null) {
            throw new \Exception('capture already processed');
        }

        Lock::acquire(self::LOG_LOCK_KEY);
        //build request and send it! updated processed to current timestamp
        $params = $this->payone->buildCaptureRequest($txid, $result[self::COLUMN_AMOUNT], $result[self::COLUMN_CURRENCY], json_decode($result[self::COLUMN_DATA], true));
        $this->serverService->serverToServerRequest($params);

        Lock::release(self::LOG_LOCK_KEY);
    }
}