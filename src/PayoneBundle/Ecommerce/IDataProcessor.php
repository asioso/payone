<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace PayoneBundle\Ecommerce;


use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\AbstractCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;

/**
 * Interface IDataProcessor
 * @package PayoneBundle\Ecommerce
 */
interface IDataProcessor
{

    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @return array
     */
    public function retrievePersonalData(AbstractPaymentInformation &$information, AbstractCart &$cart);


    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @return array
     */
    public function retrieveShippingData(AbstractPaymentInformation &$information, AbstractCart &$cart);


}