<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace PayoneBundle\Ecommerce\PaymentManager;


use AppBundle\Ecommerce\OrderMail\IOrderMailer;
use AppBundle\Ecommerce\OrderMail\OrderMailer;
use AppBundle\Model\Customer;
use AppBundle\Model\DefaultProduct;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Stream;
use PayoneBundle\Registry\IRegistry;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\Cart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\CartItem;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\ICart;
use Pimcore\Bundle\EcommerceFrameworkBundle\CheckoutManager\CheckoutManager;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\InvalidConfigException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\AbstractPaymentInformation;
use Pimcore\Bundle\EcommerceFrameworkBundle\Model\Currency;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\IStatus;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Payment\IPayment;
use Pimcore\Bundle\EcommerceFrameworkBundle\PaymentManager\Status;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\IPrice;
use Pimcore\Bundle\EcommerceFrameworkBundle\PriceSystem\Price;
use Pimcore\Bundle\EcommerceFrameworkBundle\Type\Decimal;
use Pimcore\FeatureToggles\Features\DebugMode;
use Pimcore\Logger;
use Pimcore\Model\DataObject\OnlineShopOrder;
use Pimcore\Model\DataObject\Product;
use Pimcore\Tool;
use Pimcore\Tool\RestClient\Exception;
use Psr\Http\Message\StreamInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Templating\EngineInterface;
use PayoneBundle\Ecommerce\PaymentManager\Helper\Payone;
use PayoneBundle\Ecommerce\IDataProcessor;
use Pimcore\Bundle\EcommerceFrameworkBundle\CartManager\AbstractCart;

/**
 * Class BsPayone
 * @package PayoneBundle\Ecommerce\PaymentManager
 */
class BsPayone implements IPayment
{


    // supported hashing algorithms
    const HASH_ALGO_MD5 = 'md5';
    //const HASH_ALGO_HMAC_SHA512 = 'hmac_sha512';
    const HASH_ALGO_HMAC_SHA384 = 'hmac_sha384';

    //supported payment methods
    const METHOD_SEPA = "SEPA";
    const METHOD_GIROPAY = "GIROPAY";
    const METHOD_PAYPAL = "PAYPAL";
    const METHOD_SOFORT = "SOFORT";
    const METHOD_PAYDIRECT = "PAYDIRECT";
    const METHOD_CCARD = "CCARD";
    const METHOD_INVOICE = "INVOICE";

    //
    const ENCODED_ORDERIDENT_DELIMITER = '_';
    const ENCODED_ORDERIDENT_PREFIX_REPLACEMENT = "";
    const ENCODED_ORDERIDENT_PREFIX = "payment_";


    //payment states
    const PAYMENT_RETURN_STATE_SUCCESS = 'success';
    const PAYMENT_RETURN_STATE_FAILURE = 'failure';
    const PAYMENT_RETURN_STATE_CANCEL = 'cancel';
    const PAYMENT_RETURN_STATE_PENDING = 'pending';



    /**
     * @var FormFactoryInterface
     */
    private $formFactory;

    /**
     * @var EngineInterface
     */
    private $templatingEngine;

    /**
     * @var SessionInterface
     */
    private $session;


    /**
     * @var string
     */
    protected $aid;

    /**
     * @var string
     */
    protected $mid;

    /**
     * @var string
     */
    protected $portalid;

    /**
     * @var string
     */
    protected $key;

    /**
     * @var string
     */
    protected $mode = 'test';

    /**
     * @var double
     */
    protected $apiversion = '3.8';

    /**
     * @var string
     */
    protected $encoding = 'UTF-8';

    /**
     * Keep old implementation for backwards compatibility
     *
     * @var string
     */
    protected $hashAlgorithm = self::HASH_ALGO_MD5;

    /**
     * @var
     */
    protected $optionalPaymentProperties;
    /**
     * @var
     */
    protected $paymentMethods;

    /**
     * @var array
     */
    protected $authorizedData;

    /**
     * @var
     */
    protected $selectionPartial;

    /**
     * @var $paymentInformation AbstractPaymentInformation
     */
    private $paymentInformation;

    /**
     * @var
     */
    private $js;
    /**
     * @var
     */
    private $iframeCssUrl;
    /**
     * @var
     */
    private $language;
    /**
     * @var IOrderMailer
     */
    private $mailer;

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var IRegistry
     */
    private $registry;

    /**
     * @var $processor IDataProcessor
     */
    private $processor;



    /**
     * BsPayone constructor.
     * @param array $options
     * @param EngineInterface $templatingEngine
     * @param SessionInterface $session
     * @param FormFactoryInterface $formFactory
     * @param IOrderMailer $mailer
     * @param LoggerInterface $logger
     */
    public function __construct(array $options, EngineInterface $templatingEngine, SessionInterface $session, FormFactoryInterface $formFactory, IOrderMailer $mailer, LoggerInterface $logger, IRegistry $registry)
    {
        $this->formFactory = $formFactory;

        $this->processOptions(
            $this->configureOptions(new OptionsResolver())->resolve($options)
        );
        $this->templatingEngine = $templatingEngine;
        $this->session = $session;
        $this->mailer = $mailer;
        $this->mode = getenv('PAYONE_MODE');

        $this->logger = $logger;
        $this->registry = $registry;
    }

    /**
     * @param array $options
     */
    protected function processOptions(array $options)
    {
        $this->aid = $options['aid'];
        $this->mid = $options['mid'];
        $this->portalid = $options['portalid'];
        $this->key = $options['key'];

        if (isset($options['mode'])) {
            $this->mode = $options['mode'];
        }

        if (isset($options['api_version'])) {
            $this->apiversion = $options['api_version'];
        }

        if (isset($options['encoding'])) {
            $this->encoding = $options['encoding'];
        }

        if (isset($options['js'])) {
            $this->js = $options['js'];
        }
        if (isset($options['iframe_css_url'])) {
            $this->iframeCssUrl = $options['iframe_css_url'];
        }

        if (isset($options['hash_algorithm'])) {
            $this->hashAlgorithm = $options['hash_algorithm'];
        }

        if (isset($options['optional_payment_properties'])) {
            $this->optionalPaymentProperties = array_unique(array_merge(
                $this->optionalPaymentProperties,
                $options['optional_payment_properties']
            ));
        }
        if (isset($options['partial'])) {
            $this->selectionPartial = $options['partial'];
        }


        if (isset($options['payment_methods'])) {
            $this->paymentMethods = $options['payment_methods'];
        }

        if (isset($options['data_processor'])) {
            $class = $options['data_processor'];

            $instance  = new $class();
            if(!$instance instanceof IDataProcessor ){
                throw new \Exception(sprintf('your DataProcessor must implement interface %s', IDataProcessor::class ));
            }

            $this->processor = $instance;
        }

    }

    /**
     * @param OptionsResolver $resolver
     * @return OptionsResolver
     */
    protected function configureOptions(OptionsResolver $resolver): OptionsResolver
    {
        $resolver->setRequired([
            'aid',
            'mid',
            'portalid',
            'key',
            'payment_methods',
            'partial'
        ]);


        $resolver
            ->setDefined('mode')
            ->setAllowedTypes('mode', ['string']);

        $resolver
            ->setDefined('api_version')
            ->setAllowedTypes('api_version', ['double']);

        $resolver
            ->setDefined('encoding')
            ->setAllowedTypes('encoding', ['string']);

        $resolver
            ->setDefined('iframe_css_url')
            ->setAllowedTypes('iframe_css_url', 'string');

        $resolver->setDefined('js');
        $resolver->setDefined('iframe_css_url');

        $resolver
            ->setDefined('hash_algorithm')
            ->setAllowedValues('hash_algorithm', [
                self::HASH_ALGO_MD5,
                self::HASH_ALGO_HMAC_SHA384
            ]);

        $resolver
            ->setDefined('optional_payment_properties')
            ->setAllowedTypes('optional_payment_properties', 'array');

        $resolver
            ->setDefined('payment_methods')
            ->setAllowedTypes('payment_methods', 'array');

        $resolver
            ->setDefined('data_processor')
            ->setAllowedTypes('data_processor', 'string');

        $notEmptyValidator = function ($value) {
            return !empty($value);
        };

        foreach ($resolver->getRequiredOptions() as $requiredProperty) {
            $resolver->setAllowedValues($requiredProperty, $notEmptyValidator);
        }

        return $resolver;
    }

    /**
     * @param null $method
     * @return mixed
     */
    protected function getMinimalDefaultParameters($method = null)
    {

        // collect payment data
        $paymentData['aid'] = $this->aid;
        $paymentData['mid'] = $this->mid;
        $paymentData['portalid'] = $this->portalid;
        $paymentData['key'] = hash(self::HASH_ALGO_MD5, $this->key);
        $paymentData['api_version'] = $this->apiversion;
        $paymentData['mode'] = $this->mode;
        $paymentData['encoding'] = $this->encoding;

        //override default with method specific configs...
        if ($method) {
            if (isset($this->paymentMethods[$method]['aid'])) {
                $paymentData['aid'] = $this->paymentMethods[$method]['aid'];
            }
            if (isset($this->paymentMethods[$method]['mid'])) {
                $paymentData['mid'] = $this->paymentMethods[$method]['mid'];
            }
            if (isset($this->paymentMethods[$method]['portalid'])) {
                $paymentData['portalid'] = $this->paymentMethods[$method]['portalid'];
            }
            if (isset($this->paymentMethods[$method]['key'])) {
                $paymentData['key'] = hash(self::HASH_ALGO_MD5, $this->paymentMethods[$method]['key']);
            }
            if (isset($this->paymentMethods[$method]['api_version'])) {
                $paymentData['api_version'] = $this->paymentMethods[$method]['api_version'];
            }
            if (isset($this->paymentMethods[$method]['mode'])) {
                $paymentData['mode'] = $this->paymentMethods[$method]['mode'];
            }
            if (isset($this->paymentMethods[$method]['encoding'])) {
                $paymentData['encoding'] = $this->paymentMethods[$method]['encoding'];
            }
        }

        return $paymentData;
    }

    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @param string $lang
     * @return array
     */
    public function getShippingConfig(AbstractPaymentInformation &$information, AbstractCart &$cart, $lang = "de")
    {

        $data =  $this->processor->retrieveShippingData($information, $cart);

        return $data;
    }

    /**
     * @param AbstractPaymentInformation $information
     * @param AbstractCart $cart
     * @param string $lang
     * @return array
     */
    public function getPersonalConfig(AbstractPaymentInformation &$information, AbstractCart &$cart, $lang = "de")
    {

        $data =  $this->processor->retrievePersonalData($information, $cart);
        $data['language'] = $lang;

        return $data;

    }


    /**
     * @return string
     */
    public function getName()
    {
        return "BsPayone";
    }

    /**
     * Start payment
     *
     * @param IPrice $price
     * @param array $config
     *
     * @return mixed - either an url for a link the user has to follow to (e.g. paypal) or
     *                 an symfony form builder which needs to submitted (e.g. datatrans and wirecard)
     * @throws Exception
     */
    public function initPayment(IPrice $price, array $config)
    {
        $required = $this->getRequiredRequestFields();
        foreach ($required as $property => $null) {
            $paymentData[$property] = $config[$property];
        }

        $this->language = $config['language'];
        $params = [];

        $params['paymentMethods'] = $this->getAvailablePaymentOption($this->paymentMethods, $config['cart']);

        $params['payoneFrontendScript'] = $this->js;
        $params['javascriptUrl'] = $this->js;


        $orderIdent = $config['orderIdent'];

        $fields = [
            'aid' => $this->aid,
            'encoding' => $this->encoding,
            'mid' => $this->mid,
            'mode' => $this->mode,
            'portalid' => $this->portalid,
            'request' => 'creditcardcheck',
            'responsetype' => 'JSON',
            'storecarddata' => 'yes'
        ];


        $requestFingerprint = $this->generateFingerPrint($fields);
        $fields['hash'] = $requestFingerprint;


        if (null !== $this->iframeCssUrl) {
            $params['iframeCssUrl'] = Tool::getHostUrl() . $this->iframeCssUrl;
        }

        $params['fields'] = $fields;

        return $this->templatingEngine->render($this->selectionPartial, $params);

    }

    /**
     * @param IPrice $price
     * @return float|int|
     */
    private function getAmount(IPrice $price)
    {
        //to cents
        $price = (Decimal::create($price->getGrossAmount())->mul(100)->withScale(0)->asNumeric());

        return (string)intval($price);

    }

    /**
     * @param $config
     * @return array|StreamInterface
     * @throws \Exception
     */
    public function getSepaManadateStatus($config)
    {
        /** @var ICart $cart */
        if (!$cart = $config['cart']) {
            throw new \Exception('no cart sent');
        }

        $minConfig = $this->getMinimalDefaultParameters(self::METHOD_SEPA);
        $personalConfig = $this->getPersonalConfig($config['paymentInfo'], $cart);
        $invoiceData = $this->getInvoiceData($cart);

        $personalConfig = array_merge($personalConfig, $invoiceData);
        /** @var IPrice $price */
        $price = $config['price'] ?: $cart->getPriceCalculator()->getGrandTotal();

        $parameters = array(
            "clearingtype" => "elv", // rec for invoice
            'currency' => $price->getCurrency()->getShortName(),
            "request" => "managemandate",
            "iban" => $config['iban'],  // Test data for Sofort
            "bic" => $config['bic'],
        );

        $postFields = array_merge($minConfig, $personalConfig, $parameters);

        try {
            $this->logger->info('sending to payone :' . var_export($postFields, true));

            $result = $this->serverToServerRequest($postFields);
            $return = array();

            if ($result['mandate_status'] == 'active') {
                $return = array(
                    'status' => $result['mandate_status'],
                    'mandate' => $result['mandate_identification'],
                );

            } else {
                $return = array(
                    'status' => $result['mandate_status'],
                    'mandate' => $result['mandate_identification'],
                    'mandate_text' => $result['mandate_text'],
                );
            }
            return $return;

        } catch (\Exception $e) {
            $result = array(
                'status' => 'ERROR',
                'customermessage' => $e->getMessage(),
            );

        } catch (GuzzleException $e) {
            $this->logger->error('payone.network_error : ' . $e->getMessage() . ': ' . var_export($postFields, true));
            $result = array('status' => 'ERROR', 'message' => $e->getMessage(), 'transMessage' => "payone.network_error");

        }
        return $result;

    }

    /**
     * @param $config
     * @return mixed
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException
     */
    public function getInitPaymentRedirectUrl($config)
    {
        /** @var ICart $cart */
        if (!$cart = $config['cart']) {
            throw new \Exception('no cart sent');
        }

        $orderIdent = $this->encodeOrderIdent($config['paymentInfo']->getInternalPaymentId());
        $paymentType = $config['paymentType'] ? $config['paymentType'] : $_REQUEST['paymentType'];
        $method = null;

        $this->setPaymentInformation($config['paymentInfo']);
        $availableMethods = $this->getAvailablePaymentOption($this->paymentMethods, $cart);

        //check if paymentType is available for this order!
        if (!array_key_exists($paymentType, $availableMethods)) {
            return array('status' => 'ERROR');
        }
        //setup post fields
        $postFields = $this->setupPostParametersFor($paymentType, $cart, $orderIdent, $config);


        try {
            $this->logger->info('sending to payone :' . var_export($postFields, true));
            $result = $this->serverToServerRequest($postFields);
        } catch (GuzzleException $e) {
            $this->logger->error('payone.network_error : ' . $e->getMessage() . ': ' . var_export($postFields, true));

            return array('status' => 'ERROR', 'message' => $e->getMessage(), 'transMessage' => "payone.network_error");

        } catch (\Exception $e) {
            $this->logger->error('seamless error: ' . $e->getMessage() . ': ' . var_export($postFields, true));
            Logger::error('seamless error: ' . $e->getMessage() . ': ' . var_export($postFields, true));

            return array('status' => 'ERROR', 'message' => $e->getMessage(), 'transMessage' => 'payment.error.occured', 'customermessage' => $e->getMessage());
        }


        $result['_payment_method'] = $method;
        //to store intermediate results
        $result['reference'] = $orderIdent;

        $redirectURL = $result['redirecturl'];

        if ($result['status'] == "APPROVED") {
            $result['redirecturl'] = $config['successURL'];
            $redirectURL = $result['redirecturl'];
            $result['status'] = 'REDIRECT';
        }

        if (!$redirectURL) {
            if (\Pimcore::inDebugMode(DebugMode::LOG)) {
                Logger::error('seamless result ERROR: ' . var_export($result, true));
            }
            $this->logger->error('seamless result ERROR: ' . var_export($result, true));
            $result = array('status' => 'ERROR', 'message' => $result['customermessage'], 'customermessage' => $result['customermessage']);
        }

        return $result;


    }

    /**
     * @param $paymentType
     * @param $cart
     * @param $orderIdent
     * @param $config
     * @throws \Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException
     * @return array
     */
    private function setupPostParametersFor($paymentType, $cart, $orderIdent, $config)
    {

        /** @var IPrice $price */
        $price = $config['price'] ?: $cart->getPriceCalculator()->getGrandTotal();

        //handle RESPONSE THE PROPER WAY!
        $commitOrderProcessor = Factory::getInstance()->getCommitOrderProcessor();

        $orderIdent = $this->encodeOrderIdent($config['paymentInfo']->getInternalPaymentId());
        $confirmURL = $config['confirmURL'];

        if (strpos($confirmURL, '?') === false) {
            $confirmURL .= '?orderIdent=';
        } else {
            $confirmURL .= '&orderIdent=';
        }

        $confirmURL .= urlencode($orderIdent);

        $parameters = array();
        $minConfig = $this->getMinimalDefaultParameters($paymentType);
        $personalConfig = $this->getPersonalConfig($config['paymentInfo'], $cart);
        $shippingData = $this->getShippingConfig($config['paymentInfo'], $cart);
        $personalConfig = array_merge($personalConfig, $shippingData);
        $invoiceData = $this->getInvoiceData($cart);
        $personalConfig = array_merge($personalConfig, $invoiceData);

        switch ($paymentType) {

            case self::METHOD_GIROPAY:

                $personalConfig['birthday'] = $config['birthday'];

                $parameters = array(
                    "request" => "authorization",
                    "clearingtype" => "sb",             // sb for Online Bank Transfer
                    "onlinebanktransfertype" => "GPY",  // PNT for Sofort
                    "bankcountry" => $config['bankCountry'],
                    "iban" => $config['iban'],  // Test data for Sofort
                    "bic" => $config['bic'],
                    "amount" => $this->getAmount($price),
                    'currency' => $price->getCurrency()->getShortName(),
                    "reference" => $orderIdent,
                    "narrative_text" => $config['orderDescription'] ?: $config['paymentInfo']->getInternalPaymentId(),
                    "successurl" => $config['successURL'],
                    "errorurl" => $config['failureURL'],
                    "backurl" => $config['cancelURL'],
                );
                $method = self::METHOD_GIROPAY;
                break;

            // get details!
            case self::METHOD_INVOICE:

                $method = self::METHOD_INVOICE;
                $parameters = array(
                    "clearingtype" => "rec", // rec for invoice
                    "reference" => $orderIdent,
                    "amount" => $this->getAmount($price),
                    'currency' => $price->getCurrency()->getShortName(),
                    "request" => "authorization",
                    "successurl" => $config['successURL'],
                    "errorurl" => $config['failureURL'],
                    "backurl" => $config['cancelURL'],
                    "clearingsubtype" => "POV",
                );

                break;

            case self::METHOD_SEPA:

                $parameters = array(
                    "clearingtype" => "elv", // rec for invoice
                    "reference" => $orderIdent,
                    "amount" => $this->getAmount($price),
                    'currency' => $price->getCurrency()->getShortName(),
                    "request" => "authorization",
                    "successurl" => $config['successURL'],
                    "errorurl" => $config['failureURL'],
                    "backurl" => $config['cancelURL'],
                    'iban' => $config['iban'],
                    'bic' => $config['bic'],
                    'mandate_identification' => $config['mandate_identification'],
                );
                $method = self::METHOD_SEPA;

                break;

            case self::METHOD_CCARD:

                $pseudocardpan = $config['pseudocardpan'];

                $parameters = array(
                    "clearingtype" => "cc", // cc for credit card
                    "reference" => $orderIdent,
                    "amount" => $this->getAmount($price),
                    'currency' => $price->getCurrency()->getShortName(),
                    "request" => "authorization",
                    "successurl" => $config['successURL'],
                    "errorurl" => $config['failureURL'],
                    "backurl" => $config['cancelURL'],
                    "pseudocardpan" => $pseudocardpan // pseudo card pan received from previous checkout steps, no other card details required
                );
                $method = self::METHOD_CCARD;

                break;

            case self::METHOD_PAYDIRECT:


                $method = self::METHOD_PAYDIRECT;
                $parameters = array(
                    "request" => "authorization",
                    "clearingtype" => "wlt", // wallet clearing type
                    "wallettype" => "PDT", // PDT for Paydirekt
                    "amount" => $this->getAmount($price),
                    'currency' => $price->getCurrency()->getShortName(),
                    "reference" => $orderIdent,
                    #"narrative_text" => $config['orderDescription'] ?: $config['paymentInfo']->getInternalPaymentId(),
                    "successurl" => $config['successURL'],
                    "errorurl" => $config['failureURL'],
                    "backurl" => $config['cancelURL'],
                    /**
                     * These are specific Paydirekt parameters. Paydirekt can verify the age of the customer.
                     * If it's below the add_paydata[minimum_age], the payment will be refused and the customer
                     * will be redirected to the URL defined in add_paydata[redirect_url_after_age_verification_failure]
                     */
                    //"add_paydata[minimum_age]" => "18",  //TODO!
                    //"add_paydata[redirect_url_after_age_verification_failure]" => "https://yourshop.de/payment/tooyoung", //TODO!
                );
                break;

            case self::METHOD_PAYPAL:


                $parameters = array(
                    "request" => "authorization",
                    "clearingtype" => "wlt", // wallet clearing type
                    "wallettype" => "PPE", // PPE for Paypal
                    "amount" => $this->getAmount($price),
                    'currency' => $price->getCurrency()->getShortName(),
                    "reference" => $orderIdent,
                    "narrative_text" => $config['orderDescription'] ?: $config['paymentInfo']->getInternalPaymentId(),
                    "successurl" => $config['successURL'],
                    "errorurl" => $config['failureURL'],
                    "backurl" => $config['cancelURL'],
                );

                break;

            case self::METHOD_SOFORT:

                $klarnaName = $personalConfig['firstname'] . ' ' . $personalConfig['lastname'];
                if (strlen($klarnaName) <= 27) {
                    $personalConfig['lastname'] = $klarnaName;
                    unset($personalConfig['firstname']);
                }


                $method = self::METHOD_SOFORT;
                $parameters = array(
                    "request" => "authorization",
                    "clearingtype" => "sb",             // sb for Online Bank Transfer
                    "onlinebanktransfertype" => "PNT",  // PNT for Sofort
                    //"bankaccount" => "12345678",
                    //"bankcountry" => $config['bankcountry'],
                    //"bankcode" => "88888888",
                    //"iban" => $config['iban'],  // Test data for Sofort
                    //"bic" => $config['bic'],
                    "amount" => $this->getAmount($price),
                    'currency' => $price->getCurrency()->getShortName(),
                    "reference" => $orderIdent,
                    "narrative_text" => $config['orderDescription'] ?: $config['paymentInfo']->getInternalPaymentId(),
                    "successurl" => $config['successURL'],
                    "errorurl" => $config['failureURL'],
                    "backurl" => $config['cancelURL'],
                );
                break;

        }

        return array_merge($minConfig, $personalConfig, $parameters);

    }


    /**
     * @param mixed $response
     *
     * @return IStatus
     *
     * @throws \Exception
     */
    public function handleResponse($response)
    {


        Logger::info('payone seamless response' . var_export($response, true));
        $this->logger->info('payone seamless response' . var_export($response, true));

        $orderIdent = $response['reference'];
        $orderIdent = $this->decodeOrderIdent($orderIdent);

        $authorizedData = [
            'reference' => null,
            'txaction' => null,
            'transaction_status' => null,
            'invoiceid' => null,
            'status' => null,
            'txid' => null,
            'userid' => null,
            'price' => null,
            'currency' => null,
            'mode' => null,
            'clearingtype' => null,
            'clearing_bankaccountholder' => null,
            'clearing_bankcountry' => null,
            'clearing_bankaccount' => null,
            'clearing_bankcode' => null,
            'clearing_bankiban' => null,
            'clearing_bankbic' => null,
            'clearing_bankcity' => null,
            'clearing_bankname' => null,
            'clearing_instructionnote' => null,
        ];

        //catch error state early
        if ($response['status'] && in_array($response['status'], ['ERROR'])) {
            $status = new Status(
                $orderIdent,
                $response['reference'],
                $response['errormessage'],
                IStatus::STATUS_CANCELLED,
                [
                    'response' => json_encode($response)
                ]
            );

            Logger::debug('#payone response status: ' . var_export($status, true));
            $this->logger->debug('#payone response status: ' . var_export($status, true));

            return $status;
        }


        if ($this->validateKey($response['key'])) {
            $authorizedData = array_intersect_key($response, $authorizedData);
            $authorizedData['response'] = var_export($response, true);

            #Logger::info('seamless authorized data' . var_export($authorizedData, true));
            $this->logger->info('seamless authorized data' . var_export($authorizedData, true));

            $this->setAuthorizedData($authorizedData);

            $price = new Price(Decimal::create($authorizedData['price']), new Currency($authorizedData['currency']));

            $status = new Status(
                $orderIdent,
                $response['reference'],
                $response['transaction_status'],
                ($response['reference'] !== null && (($response['txaction'] == 'appointed') || ($response['txaction'] == 'paid')))
                    ? IStatus::STATUS_AUTHORIZED
                    : IStatus::STATUS_CANCELLED,
                [
                    'reference' => $response['reference'],
                    'internal' => $orderIdent,
                    'txaction' => $response['txaction'],
                    'transaction_status' => $response['transaction_status'],
                    'price' => (string)$price,
                    'currency' => $response['currency'],
                    'mode' => $response['mode'],
                    'clearingtype' => $response['clearingtype'],
                    'response' => var_export($response, true),
                ]
            );

            #Logger::info('#payone response status: ' . var_export($status, true));
            $this->logger->info('#payone response status: ' . var_export($status, true));

            return $status;
        }


        $status = new Status(
            $orderIdent,
            $response['reference'],
            $response['transaction_status'],
            ($orderIdent !== null && (($response['transaction_status'] == 'PENDING')))
                ? IStatus::STATUS_PENDING
                : IStatus::STATUS_CANCELLED,
            [
                'userId' => $response['userid'],
                'txid' => $response['txid'],
                'response' => print_r($response, true),

            ]
        );

        return $status;

    }

    /**
     * Returns the authorized data from payment provider
     *
     * @return array
     */
    public function getAuthorizedData()
    {
        return $this->authorizedData;
    }

    /**
     * Set authorized data from payment provider
     *
     * @param array $authorizedData
     */
    public function setAuthorizedData(array $authorizedData)
    {
        $this->authorizedData = $authorizedData;
    }

    /**
     * Executes payment
     *
     * @param IPrice $price
     * @param string $reference
     *
     * @return IStatus
     */
    public function executeDebit(IPrice $price = null, $reference = null)
    {
        throw new NotImplementedException('executeDebit is not implemented yet.');
    }

    /**
     * Executes credit
     *
     * @param IPrice $price
     * @param string $reference
     * @param $transactionId
     *
     * @return IStatus
     */
    public function executeCredit(IPrice $price, $reference, $transactionId)
    {
        throw new NotImplementedException('executeCredit is not implemented yet.');
    }


    public function setPaymentInformation(AbstractPaymentInformation $information)
    {
        $this->paymentInformation = $information;
    }


    /**
     * @return array
     */
    protected function getRequiredRequestFields(): array
    {
        return [
//            'successURL'       => null,
//            'cancelURL'        => null,
//            'failureURL'       => null,
//            'serviceURL'       => null,
//            'orderDescription' => null,
//            'orderIdent'       => null,
//            'language'         => null,
        ];
    }

    /**
     * @param $paymentMethods
     * @param Cart $cart
     * @return mixed
     */
    private function getAvailablePaymentOption(&$paymentMethods, ICart $cart)
    {
        if ($this->paymentInformation) {

            if (($object = $this->paymentInformation->object) instanceof OnlineShopOrder) {

                /*
                 * as an example disable some method because of cart content!
                 */

                //unset($paymentMethods[self::METHOD_SOFORT]);


            }
        }

        return $paymentMethods;
    }

    /**
     * @param AbstractPaymentInformation $paymentInfo
     * @return mixed|null
     */
    public static function extractSeamlessResponse(AbstractPaymentInformation $paymentInfo)
    {
        if ($providerData = $paymentInfo->getProviderData()) {
            $providerData = json_decode($providerData);
            if ($providerData['seamless_response']) {
                return json_decode($providerData['seamless_response']);
            }
        }
        return null;
    }

    /**
     * @param $params
     * @return array|\Psr\Http\Message\StreamInterface
     * @throws \Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function serverToServerRequest($params)
    {
        /**
         * This should return something like:
         * Array
         * (
         *  [status] => REDIRECT
         *  [redirecturl] => https://www.sandbox.paypal.com/webscr?useraction=commit&cmd=_express-checkout&token=EC-4XXX73XXXK03XXX1A
         *  [txid] => 205387102
         *  [userid] => 90737467
         * )
         */
        $response = Payone::sendRequest($params);
        //todo handle streamInterface?
        if ($response instanceof Stream) {
            $response = Payone::parseResponse((string)$response->getContents()); // returns all the contents
        }


        return $response;
    }

    /**
     * @param $fields
     * @return string
     * @throws Exception
     */
    protected function generateFingerprint($fields)
    {
        // hash calculated over your request-parameter-values (alphabetical request-order) plus PMI portal key
        ksort($fields);
        $seed = "";
        foreach ($fields as $key => $value) {
            $seed .= $value;
        }
        return $this->calculateFingerprint($seed);

    }

    /**
     * @param $requestFingerprintSeed
     * @return string
     * @throws Exception
     */
    protected function calculateFingerprint($requestFingerprintSeed)
    {
        if ($this->hashAlgorithm === self::HASH_ALGO_MD5) {
            $requestFingerprint = md5($requestFingerprintSeed . $this->key);
            #Logger::debug('#wirecard generateFingerprint (hmac): ' . $requestFingerprintSeed);
        } else {
            $requestFingerprint = hash_hmac('sha384', $requestFingerprintSeed, $this->key);
            #Logger::debug('#wirecard generateFingerprint: ' . $requestFingerprintSeed);
        }

        return $requestFingerprint;
    }

    /**
     * @param $requestkeyHash
     * @return bool
     */
    protected function verifyKey($requestkeyHash)
    {
        if ($this->hashAlgorithm === self::HASH_ALGO_MD5) {
            $keyHash = md5($this->key);
        } else {
            $keyHash = hash_hmac('sha384', $this->key, $this->key);
        }

        return $keyHash == $requestkeyHash;
    }


    /**
     * @param $orderIdent
     * @return string
     * @throws \Exception
     */
    protected function encodeOrderIdent($orderIdent)
    {
        $string = str_replace('~', self::ENCODED_ORDERIDENT_DELIMITER, $orderIdent);
        $string = str_replace(self::ENCODED_ORDERIDENT_PREFIX, self::ENCODED_ORDERIDENT_PREFIX_REPLACEMENT, $string);
        try {
            $external = $this->registry->generateAndStoreExternalReference($string);

            return $external;
        } catch (\Exception $exception) {
            //
        }
        return $string;

    }

    /**
     * @param $orderIdent
     * @return string
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function decodeOrderIdent($orderIdent)
    {
        //check if the internal PaymentId has been used (legacy case - skip lookUp instead)
        if (!preg_match('/(\\-|_)/', $orderIdent)) {
            try {
                $orderIdent = $this->registry->getInternalByExternalReference($orderIdent);
            } catch (\Exception $e) {
                //
            }
        }

        $string = str_replace(self::ENCODED_ORDERIDENT_DELIMITER, '~', $orderIdent);
        return self::ENCODED_ORDERIDENT_PREFIX . $string;
    }

    private function getInvoiceData(ICart $cart)
    {
        //article number, quantity, description, price and VAT.
        //id[2],pr[2],no[2],de[2],va[2]
        $cartData = array();

        $c = 1; //starts at 1!!!!
        foreach ($cart->getItems() as $cartItem) {
            $cartData["id[$c]"] = $cartItem->getProduct()->getSku();
            //needs to be in cents
            $cartData["pr[$c]"] = (string)(Decimal::create($cartItem->getPrice()->getGrossAmount())->mul(100)->withScale(0)->asNumeric());
            $cartData["no[$c]"] = (string)$cartItem->getCount();
            $cartData["de[$c]"] = $cartItem->getProduct()->getName();
            if (!empty($cartItem->getPrice()->getTaxEntries())) {
                $cartData["va[$c]"] = number_format($cartItem->getPrice()->getTaxEntries()[0]->getPercent(), 0, '.', '');
            }
            $cartData["it[$c]"] = "goods";
            $c++;
        }


        foreach ($cart->getPriceCalculator()->getPriceModifications() as $name => $modification) {

            if ($modification->getAmount()->asRawValue() < 0) {
                // CART VOUCHER
                $id = $modification->getDescription() != null ? $modification->getDescription() : "Rabatt";
                $cartData["id[$c]"] = $id;
                //needs to be in cents
                $cartData["pr[$c]"] = (string)(Decimal::create($modification->getAmount())->mul(100)->withScale(0)->asNumeric());
                $cartData["no[$c]"] = (string)1;
                $cartData["de[$c]"] = $id;
                $cartData["it[$c]"] = "voucher";
                $c++;
            } else {
                //must be SHIPPING
                $id = $modification->getDescription() != null ? $modification->getDescription() : "Versandkosten";
                $cartData["id[$c]"] = $id;
                //needs to be in cents
                $cartData["pr[$c]"] = (string)(Decimal::create($modification->getAmount())->mul(100)->withScale(0)->asNumeric());
                $cartData["no[$c]"] = (string)1;
                $cartData["de[$c]"] = $id;
                $cartData["it[$c]"] = "shipment";
                $c++;
            }
        }


        return $cartData;


    }


    private function validateKey($key)
    {
        if ($key == hash(self::HASH_ALGO_MD5, $this->key)) {
            return true;
        }

        foreach ($this->paymentMethods as $method) {
            if (isset($method['key'])) {
                if ($key == hash(self::HASH_ALGO_MD5, $method['key'])) {
                    return true;
                }
            }

        }
        return false;

    }

}