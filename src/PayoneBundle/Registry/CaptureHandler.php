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

    }

    /**
     * @param string $txid
     * @param string $reference
     * @param string $amount
     * @param string $currency
     * @param array $options
     */
    public function addCapture(string $txid,string  $reference,string  $amount,string  $currency, array $options): void
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
     * @param BsPayone $payone
     * @return mixed
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function resolveCapture($txid, BsPayone $payone)
    {

        $db = Db::get();
        $result = $db->fetchRow(
            "SELECT * FROM " . self::LOG_TABLE_NAME . " WHERE `" . self::COLUMN_TXID . "` = ?",
            [$txid]
        );


        if($result && $result['processed'] == null){
            Lock::acquire(self::LOG_LOCK_KEY);
            $now = CarbonImmutable::now();
            //build request and send it! updated processed to current timestamp
            $params = $payone->buildCaptureRequest($txid, $result[self::COLUMN_AMOUNT], $result[self::COLUMN_CURRENCY], json_decode($result[self::COLUMN_DATA], true));
            $this->serverService->serverToServerRequest($params);

            $db->updateWhere(self::LOG_TABLE_NAME, [self::COLUMN_PROCESSED=> $now ], "id = ". $result['id'] );

            Lock::release(self::LOG_LOCK_KEY);
        }

    }
}