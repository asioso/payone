<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace AppBundle\Ecommerce;


use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\AbstractCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;

/**
 * Class DataProcessor
 * @package AppBundle\Ecommerce
 */
class DataProcessor implements IDataProcessor
{

    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @return array
     */
    public function retrievePersonalData(AbstractPaymentInformation &$information, AbstractCart &$cart)
    {

        $checkoutManager = Factory::getInstance()->getCheckoutManager($cart);
        $deliveryData = $checkoutManager->getCheckoutStep('deliveryaddress')->getData();


        $data = array(
            #"salutation" => "Mr.",
            "firstname" => $deliveryData->addressFirstname,
            "lastname" => $deliveryData->addressLastname,
            "street" => $deliveryData->addressStreet,
            "zip" => $deliveryData->addressZip,
            "city" => $deliveryData->addressCity,
            "country" => $deliveryData->addressCountryCode,
            "email" => $deliveryData->addressEmail,
        );

        if ($deliveryData->addressCompany) {
            $data['company'] = $deliveryData->addressCompany;
        }

        if ($deliveryData->checkDelivery == true) {
            $data = array(
                "firstname" => $deliveryData->billingFirstname,
                "lastname" => $deliveryData->billingLastname,
                "street" => $deliveryData->billingStreet,
                "zip" => $deliveryData->billingZip,
                "city" => $deliveryData->billingCity,
                "country" => $deliveryData->billingCountryCode,
                "email" => $deliveryData->billingEmail,
            );

            if ($deliveryData->billingCompany) {
                $data['company'] = $deliveryData->billingCompany;
            }
        }

        return $data;

    }


    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @return array
     */
    public function retrieveShippingData(AbstractPaymentInformation &$information, AbstractCart &$cart)
    {

        $object = $information->getObject();
        $data = array(
            "shipping_firstname" => $object->get('deliveryFirstname'),
            "shipping_lastname" => $object->get('deliveryLastname'),
            "shipping_street" => $object->get('deliveryStreet'),
            "shipping_zip" => $object->get('deliveryZip'),
            "shipping_city" => $object->get('deliveryCity'),
            "shipping_country" => $object->get('deliveryCountry'),
        );
        if ($object->get('deliveryCompany') != null) {
            $data["shipping_company"] = $object->get('deliveryCompany');
        }

        return $data;

    }

}
