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


use Symfony\Contracts\EventDispatcher\Event;

class PayoneAsyncResponseEvent extends Event
{

    /**
     * @var array
     */
    private $responseParameters;

    public function __construct( array $responseParameters)
    {
        $this->responseParameters = $responseParameters;
    }

    /**
     * @return array
     */
    public function getResponseParameters(): array
    {
        return $this->responseParameters;
    }


}