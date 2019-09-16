<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace PayoneBundle\Model;


use PayoneBundle\Ecommerce\IDataProcessor;
use PayoneBundle\Exception\PayoneConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\AbstractCart;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;

abstract class AbstractDataProcessor implements IDataProcessor
{
    const PERSONAL_FIRSTNAME = "firstname";
    const PERSONAL_LASTNAME = "lastname";
    const PERSONAL_STREET = "street";
    const PERSONAL_CITY = "city";
    const PERSONAL_ZIP = "zip";
    const PERSONAL_COUNTRY = "country";
    const PERSONAL_EMAIL = "email";
    const PERSONAL_COMPANY = "company";
    const PERSONAL_PHONE = "telephonenumber";
    const PERSONAL_VAT = "vatid";


    const SHIPPING_FIRSTNAME = "shipping_firstname";
    const SHIPPING_LASTNAME = "shipping_lastname";
    const SHIPPING_STREET = "shipping_street";
    const SHIPPING_CITY = "shipping_city";
    const SHIPPING_ZIP = "shipping_zip";
    const SHIPPING_COUNTRY = "shipping_country";
    const SHIPPING_EMAIL = "shipping_email";
    const SHIPPING_COMPANY = "shipping_company";


    const INVOICE_ITEM_ID = "id";
    const INVOICE_ITEM_NAME = "name";


    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @return array
     * @throws PayoneConfigException
     */
    public function getPersonalData(AbstractPaymentInformation &$information, AbstractCart &$cart)
    {
        $data = $this->retrievePersonalData($information, $cart);
        $this->verifyPersonalData($data);

        return $data;
    }

    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @return array
     * @throws PayoneConfigException
     */
    public function getShippingData(AbstractPaymentInformation &$information, AbstractCart &$cart)
    {
        $data = $this->retrieveShippingData($information, $cart);
        $this->verifyShippingData($data);

        return $data;
    }

    /**
     * @param $cartItem
     * @return array
     * @throws PayoneConfigException
     */
    public function getInvoiceData($cartItem)
    {
        $data = $this->retrieveInvoiceData($cartItem);
        $this->verifyInvoiceData($data);

        return $data;
    }


    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @return array
     */
    public abstract function retrievePersonalData(AbstractPaymentInformation &$information, AbstractCart &$cart);

    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @return array
     */
    public abstract function retrieveShippingData(AbstractPaymentInformation &$information, AbstractCart &$cart);

    /**
     * return an array ['id'=> <itemId>, 'name'=><itemName>]
     *  is used to generate invoice data
     * @param $cartItem
     * @return array
     */
    public abstract function retrieveInvoiceData($cartItem);

    /**
     * @param array $data
     * @throws PayoneConfigException
     */
    private function verifyPersonalData(array $data)
    {
        if ( 7 == sizeof(array_intersect_key(
            [
                self::PERSONAL_FIRSTNAME => true,
                self::PERSONAL_LASTNAME => true,
                self::PERSONAL_STREET => true,
                self::PERSONAL_ZIP => true,
                self::PERSONAL_CITY => true,
                self::PERSONAL_COUNTRY => true,
                self::PERSONAL_PHONE => true,
            ],
            $data))
        ) {
            return;
        }
        throw new PayoneConfigException('You are missing Personal Data');
    }

    /**
     * @param array $data
     * @throws PayoneConfigException
     */
    private function verifyShippingData(array $data)
    {
        if ( 6 == sizeof(array_intersect_key(
            [
                self::SHIPPING_FIRSTNAME => true,
                self::SHIPPING_LASTNAME => true,
                self::SHIPPING_STREET => true,
                self::SHIPPING_ZIP => true,
                self::SHIPPING_CITY => true,
                self::SHIPPING_COUNTRY => true,
            ],
            $data))
        ) {
            return;
        }
        throw new PayoneConfigException('You are missing Shipping Data');
    }

    /**
     * @param array $data
     * @throws PayoneConfigException
     */
    private function verifyInvoiceData(array $data)
    {
        if (2 == sizeof(array_intersect_key(
            [
                self::INVOICE_ITEM_ID => true,
                self::INVOICE_ITEM_NAME => true,
            ],
            $data))
        ) {
            return;
        }
        throw new PayoneConfigException('You are missing INVOICE Data');
    }
}