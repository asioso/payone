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


use PayoneBundle\Model\AbstractDataProcessor;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\AbstractCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICartItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;
use Pimcore\Model\DataObject\Product;

/**
 * Class DataProcessor
 * @package AppBundle\Ecommerce
 */
class DataProcessor extends AbstractDataProcessor
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
            self::PERSONAL_FIRSTNAME => $object->get('customerFirstname'),
            self::PERSONAL_LASTNAME => $object->get('customerLastname'),
            self::PERSONAL_STREET => $object->get('customerStreet'),
            self::PERSONAL_ZIP => $object->get('customerZip'),
            self::PERSONAL_CITY => $object->get('customerCity'),
            self::PERSONAL_COUNTRY => $object->get('customerCountry'),
            self::PERSONAL_EMAIL => $object->get('customerEmail'),
        );

        if ($company =  $object->get('customerCompany')) {
            $data[self::PERSONAL_COMPANY] = $company;
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
            self::SHIPPING_FIRSTNAME => $object->get('deliveryFirstname'),
            self::SHIPPING_LASTNAME => $object->get('deliveryLastname'),
            self::SHIPPING_STREET => $object->get('deliveryStreet'),
            self::SHIPPING_ZIP => $object->get('deliveryZip'),
            self::SHIPPING_CITY => $object->get('deliveryCity'),
            self::SHIPPING_COUNTRY => $object->get('deliveryCountry'),
        );
        if ($object->get('deliveryCompany') != null) {
            $data[self::SHIPPING_COMPANY] = $object->get('company');
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
                return array(self::INVOICE_ITEM_ID=> $product->getId(), self::INVOICE_ITEM_NAME=> $product->getName());
            }
            throw new \InvalidArgumentException(sprintf('%s is not supported by %s', get_class($product), get_class($this)));
        }

        throw new \InvalidArgumentException(sprintf('%s is not supported by %s', get_class($cartItem), get_class($this)));
    }
}
