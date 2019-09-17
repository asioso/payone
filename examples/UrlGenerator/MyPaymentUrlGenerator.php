<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace AppBundle\Ecommerce\UrlGenerator;


use PayoneBundle\Model\IPaymentURLGenerator;
use PayoneBundle\Model\DefaultPaymentURLGenerator;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class MyPaymentUrlGenerator
 * @package AppBundle\Ecommerce\UrlGenerator
 */
class MyPaymentUrlGenerator extends DefaultPaymentURLGenerator implements IPaymentURLGenerator
{

    public function getCompletedUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string
    {
        //as  an example
        return $this->router->generate('fooRoute', array('_locale' => $config['language'], 'id' => $paymentInformation->getObject()->getId() ) );
    }


}
