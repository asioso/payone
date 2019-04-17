<?php
/**
 * This class is a wrapper to be able to send arrays of Payone request
 * to the Payone platform.
 *
 * Payone Connector is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Payone Connector is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Payone Connector. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package Simple PHP Integration
 * @link https://www.bspayone.com/
 * @copyright (C) BS PAYONE GmbH 2016, 2018
 * @author Florian Bender <florian.bender@bspayone.com>
 * @author Timo Kuchel <timo.kuchel@bspayone.com>
 * @author Hannes Reinberger <hannes.reinberger@bspayone.com>
 *
 *
 * changes for pimcore integration:
 * @author  Fabian Pechstein <fabian.pechstein@asioso.com>
 */

namespace PayoneBundle\Ecommerce\PaymentManager\Helper;

use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;

/**
 * Class Payone
 * @package PayoneBundle\Ecommerce\PaymentManager\Helper
 */
class Payone
{

    /**
     * The URL of the Payone API
     */
    const PAYONE_SERVER_API_URL = 'https://api.pay1.de/post-gateway/';

    /**
     * performing the HTTP POST request to the PAYONE platform
     *
     * @param array $request
     * @param string $responsetype
     * @param LoggerInterface|null $logger
     * @return array|StreamInterface Returns an array of response
     *     parameters in "classic" mode, a Stream for any other mode.
     * @throws GuzzleException
     * @throws Exception
     */
    public static function sendRequest($request, $responsetype = "", LoggerInterface $logger = null)
    {
        if ($responsetype === "json") {
            // appends the accept: application/json header to the request
            // This is used to retrieve structured JSON in the response
            $client = new Client(['headers' => ['accept' => 'application/json']]);
        } else {
            // if $responsetype is set to anything else than "json", use the standard request
            $client = new Client();
        }

        $begin = microtime(true);

        if ($response = $client->request('POST', self::PAYONE_SERVER_API_URL, ['form_params' => $request])) {

            $return = self::parseResponse($response, $logger);

        } else {
            throw new Exception('Something went wrong during the HTTP request.');
        }

        $end = microtime(true);
        $duration = $end - $begin;
        $return['duration'] = $duration;

        return $return;
    }

    /**
     * gets response string an puts it into an array
     *
     * @param ResponseInterface $response
     * @param LoggerInterface|null $logger
     * @return array
     * @throws Exception
     */
    public static function parseResponse(ResponseInterface $response, LoggerInterface $logger = null)
    {
        $responseArray = array();
        $explode = explode("\n", $response->getBody());
        foreach ($explode as $e) {
            $keyValue = explode("=", $e);
            if (trim($keyValue[0]) != "") {
                if (count($keyValue) == 2) {
                    $responseArray[$keyValue[0]] = trim($keyValue[1]);
                } else {
                    $key = $keyValue[0];
                    unset($keyValue[0]);
                    $value = implode("=", $keyValue);
                    $responseArray[$key] = $value;
                }
            }
        }


        if ($logger) {
            $logger->info("server response: ".var_export($responseArray, true));
        }

        if ($responseArray['status'] == "ERROR") {

            throw new Exception($responseArray['customermessage']);
        }
        return $responseArray;
    }
}