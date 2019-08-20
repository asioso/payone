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
        if (!empty(array_intersect_key(
            [
                self::PERSONAL_FIRSTNAME,
                self::PERSONAL_LASTNAME,
                self::PERSONAL_STREET,
                self::PERSONAL_ZIP,
                self::PERSONAL_CITY,
                self::PERSONAL_COUNTRY,
                self::PERSONAL_PHONE,
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
        if (!empty(array_intersect_key(
            [
                self::SHIPPING_FIRSTNAME,
                self::SHIPPING_LASTNAME,
                self::SHIPPING_STREET,
                self::SHIPPING_ZIP,
                self::SHIPPING_CITY,
                self::SHIPPING_COUNTRY,
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
        if (!empty(array_intersect_key(
            [
                self::INVOICE_ITEM_ID,
                self::INVOICE_ITEM_NAME,
            ],
            $data))
        ) {
            return;
        }
        throw new PayoneConfigException('You are missing INVOICE Data');
    }
}