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


use PayoneBundle\Ecommerce\PaymentManager\BsPayone;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;
use Pimcore\Tool;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class DefaultPaymentURLGenerator implements IPaymentURLGenerator
{

    /**
     * @var RouterInterface
     */
    protected $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $route
     * @param array $parameters
     * @param int $referenceType
     * @return string
     */
    protected function generateUrl(string $route, array $parameters = [], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getSuccessUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string
    {
        $schema = $config['schemaAndHost'];
        $language = $config['language'];

        return $schema . $this->generateUrl('payone', ['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                'state' => BsPayone::PAYMENT_RETURN_STATE_SUCCESS, 'prefix' => $language]);
    }

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getFailureUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string
    {
        $schema = $config['schemaAndHost'];
        $language = $config['language'];

        return $schema . $this->generateUrl('payone', ['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                'state' => BsPayone::PAYMENT_RETURN_STATE_FAILURE, 'prefix' => $language]);
    }

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getCancelUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string
    {
        $schema = $config['schemaAndHost'];
        $language = $config['language'];
        return $schema . $this->generateUrl('payone', ['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                'state' => BsPayone::PAYMENT_RETURN_STATE_CANCEL, 'prefix' => $language]);
    }

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getServiceUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string
    {

        return Tool::getHostUrl();
    }

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getPendingUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string
    {
        $schema = $config['schemaAndHost'];
        $language = $config['language'];

        return $schema . $this->generateUrl('payone', ['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                'state' => BsPayone::PAYMENT_RETURN_STATE_PENDING, 'prefix' => $language]);
    }

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getConfirmUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string
    {
        $schema = $config['schemaAndHost'];

        return $schema . $this->generateUrl('payone', ['action' => 'confirm-payment-server-side', 'elementsclientauth' => 'disabled']);
    }

    /**
     * @param AbstractPaymentInformation $paymentInformation
     * @param array|null $config
     * @return string
     */
    public function getCompletedUrl(AbstractPaymentInformation $paymentInformation, array $config = null): string
    {
        $schema = $config['schemaAndHost'];
        $language = $config['language'];

        return $schema . $this->generateUrl('payone', ['action' => 'completed', 'controller' => 'checkout', 'id' => $paymentInformation->getObject()->getId(), 'prefix' => $language]);
    }
}