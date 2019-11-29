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


interface PayoneEvents
{
    const PAYONE_PRE_SEND_REQUEST_EVENT = "payone.event.request.pre";
    const PAYONE_POST_SEND_REQUEST_EVENT = "payone.event.request.post";

    const PAYONE_RESPONSE_RECEIVED_EVENT = "payone.event.response.received";
    const PAYONE_RESPONSE_PROCESSED_EVENT = "payone.event.response.processed";


}