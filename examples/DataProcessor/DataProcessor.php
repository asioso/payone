<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace AppBundle\Ecommerce\DataProcessor;


use PayoneBundle\Ecommerce\IDataProcessor;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\AbstractCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;
use Pimcore\Model\DataObject\Product;

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
     * @throws \Exception
     */
    public function retrievePersonalData(AbstractPaymentInformation &$information, AbstractCart &$cart)
    {

        $object = $information->getObject();



        $data = array(
            "firstname" => $object->get('customerFirstname'),
            "lastname" => $object->get('customerLastname'),
            "street" => $object->get('customerStreet'),
            "zip" => $object->get('customerZip'),
            "city" => $object->get('customerCity'),
            "country" => $object->get('customerCountry'),
            "email" => $object->get('customerEmail'),
        );

        if ($company =  $object->get('customerCompany')) {
            $data['company'] = $company;
        }

        return $data;

    }


    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @return array
     *
     * @throws \Exception
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
            $data["shipping_company"] = $object->get('company');
        }
        return $data;

    }

    /**
     * return an array ['id'=> <itemId>, 'name'=><itemName>]
     *  is used to generate invoice data
     * @param $cartItem
     * @return array
     */
    public function retrieveInvoiceData($cartItem)
    {
        if($cartItem instanceof ICartItem){

            $product = $cartItem->getProduct();
            if($product instanceof Product){
                return array('id'=> $product->getId(), 'name'=> $product->getName());
            }
            throw new \InvalidArgumentException(sprintf('%s is not supported by %s', get_class($product), get_class($this)));
        }

        throw new \InvalidArgumentException(sprintf('%s is not supported by %s', get_class($cartItem), get_class($this)));
    }
}
