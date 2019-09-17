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


use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;

interface IPaymentURLGenerator
{
    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getSuccessUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string;

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getFailureUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string;

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getCancelUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string;

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getServiceUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string;

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getPendingUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string;

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getConfirmUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string;

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getCompletedUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string;

}