<?php

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