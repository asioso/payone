<?php
/**
 * This source file is available under  GNU General Public License version 3 (GPLv3)
 *
 * Full copyright and license information is available in LICENSE.md which is distributed with this source code.
 *
 *  @copyright  Copyright (c) Asioso GmbH (https://www.asioso.com)
 *
 */

namespace PayoneBundle\Controller;

use AppBundle\Controller\AbstractCartAware;

use PayoneBundle\Ecommerce\PaymentManager\BsPayone;
use PayoneBundle\Registry\IRegistry;
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Document\Tag\Exception\NotFoundException;
use Pimcore\Logger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BsPayoneController
 * @package PayoneBundle\Controller
 */
class BsPayoneController extends AbstractCartAware
{

    /**
     * @var \PayoneBundle\Model\IPaymentURLGenerator
     */
    private $generator;
    /**
     * @var IRegistry
     */
    private $registry;

    /**
     * BsPayoneController constructor.
     * @param \PayoneBundle\Model\IPaymentURLGenerator $generator
     */
    public function __construct(\PayoneBundle\Model\IPaymentURLGenerator $generator, IRegistry $registry)
    {
        $this->generator = $generator;
        $this->registry = $registry;
    }

    /**
     * commits payment with payment type PREPAYMENT
     */
    public function prepaymentAction()
    {
        $checkoutManager = Factory::getInstance()->getCheckoutManager($this->getCart());
        $paymentInfo = $checkoutManager->startOrderPayment();
        $checkoutManager->handlePaymentResponseAndCommitOrderPayment(['orderIdent' => $paymentInfo->getInternalPaymentId(), 'paymentType' => 'PREPAYMENT']);
        return $this->redirect($this->generateUrl('action', [
            'prefix' => $this->view->language,
            'action' => 'completed',
            'controller' => 'checkout',
            'id' => $paymentInfo->getObject()->getId()]));
    }
    /**
     * Server side endpoint for confirming payment
     */
    public function confirmPaymentServerSideAction(Request $request)
    {
        $params = array_merge($request->query->all(), $request->request->all());
        Logger::info('##url (payone payment confirmed): '.$_SERVER['REQUEST_URI'] . '&'. http_build_query($params));

        $commitOrderProcessor = Factory::getInstance()->getCommitOrderProcessor();

        /**
         * @var $paymentProvider BsPayone
         */
        $paymentProvider = Factory::getInstance()->getPaymentManager()->getProvider('bspayone');

        if ($committedOrder = $commitOrderProcessor->committedOrderWithSamePaymentExists($params, $paymentProvider)) {
            Logger::info('Order with same payment is already committed, doing nothing. OrderId is ' . $committedOrder->getId());
        } else {

            try{

                $commitOrderProcessor->handlePaymentResponseAndCommitOrderPayment($params);

            }catch(UnsupportedException $e){

                //order is already committed, just update the payment Status
                $paymentStatus = $paymentProvider->handleResponse($params );
                $orderManager = Factory::getInstance()->getOrderManager();
                $order = $orderManager->getOrderByPaymentStatus($paymentStatus);

                $orderAgent = Factory::getInstance()->getOrderManager()->createOrderAgent($order);
                //mails should get handled by the processor implementing PayoneCommitOrderProcessorInterface
                $orderAgent->updatePayment($paymentStatus);
            }


        }


        return new Response("TSOK");
    }

    /**
     * does a precheck on the mandate status for the current customer
     *
     *
     * @param Request $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function checkMandateStatusAction(Request $request){


        if($request->isXmlHttpRequest()){
            $iban = $request->get('iban');
            $bic = $request->get('bic');

            $cart = $this->getCart();
            $checkoutManager = Factory::getInstance()->getCheckoutManager($cart);
            $paymentInformation = $checkoutManager->startOrderPayment();

            /**
             * @var $payment BsPayone
             */
            $payment = $checkoutManager->getPayment();
            $config = array(
                'iban' => $iban,
                'bic' => $bic,
                'cart' => $cart,
                'paymentInfo' => $paymentInformation,
            );

            $response = $payment->getSepaManadateStatus($config);
            $response['mandate_text'] = urldecode($response['mandate_text']);

            return new JsonResponse($response);

        }

        return new JsonResponse(array('status'=> 'foo'));

        throw new NotFoundException();


    }

    /**
     * builds config array of redirect urls for payone seamless
     */
    public function getPayoneRedirectUrlAction(Request $request)
    {
        $cart = $this->getCart();
        $checkoutManager = Factory::getInstance()->getCheckoutManager($cart);
        $language = substr($request->getLocale(), 0, 2);
        if ($checkoutManager->isCommitted()) {
            throw new \Exception('Cart already committed');
        }
        /**
         * @var $payment BsPayone
         */
        $payment = $checkoutManager->getPayment();
        $paymentInformation = $checkoutManager->startOrderPayment();
        $orderNumber = '';
        if ($order = $paymentInformation->getObject()) {
            /* @var $order \Pimcore\Model\DataObject\OnlineShopOrder */
            $orderNumber = $order->getOrdernumber();
        }
        if ($paymentType = $request->get('paymentType')) {

            $urlConfig = array(
                'schemaAndHost' => $request->getSchemeAndHttpHost(),
                'language' => $language,
            );

            $config = [
                'successURL' => $this->generator->getSuccessUrl($paymentInformation, $urlConfig),
                'failureURL' => $this->generator->getFailureUrl($paymentInformation, $urlConfig),
                'cancelURL' => $this->generator->getCancelUrl($paymentInformation, $urlConfig),
                'serviceURL' => $this->generator->getServiceUrl($paymentInformation, $urlConfig),
                'pendingURL' => $this->generator->getPendingUrl($paymentInformation, $urlConfig),
                'confirmURL' => $this->generator->getConfirmUrl($paymentInformation, $urlConfig),
                'completedURL' => $this->generator->getCompletedUrl($paymentInformation, $urlConfig),
                'pollingURL' => $this->generator->getPollingURL($paymentInformation, $urlConfig),
                'paymentInfo' => $paymentInformation,
                'paymentType' => $paymentType,
                'cart' => $this->getCart(),
                'birthday' => $request->get('birth'),
                'orderDescription' => $orderNumber,
                'reference' => $orderNumber,
                'language' => $language,
                'pseudocardpan' => $request->get('pseudocardpan'),
                'truncatedcardpan' => $request->get('truncatedcardpan'),
                'cardexpiredResponse' => $request->get('cardexpiredResponse'),
                'iban' => $request->get('iban'),
                'bic' => $request->get('bic'),
                'mandate_identification' => $request->get('mandate'),
                'bankCountry' => $request->get('bankCountry'),
                'clientIp' => empty($request->getClientIp()) ? $_SERVER['REMOTE_ADDR'] : $request->getClientIp(),
            ];

            try{
                $response = $payment->getInitPaymentRedirectUrl($config);

                if(isset($response['redirecturl'])){
                    return $this->json(['url' => $response['redirecturl'], 'status' => $response['status'] ]);
                }

                if(isset($response['status']) && $response['status']== "ERROR"){
                    //return $this->json(['error' => 'AN ERROR occurred']);

                    $checkoutManager->cancelStartedOrderPayment();

                    return $this->json(['status'=> 'ERROR' , 'message' => isset($response['customermessage']) ? $response['customermessage'] : $this->get('translator')->trans($response['transMessage'])]);
                }

            }catch(\Exception $e){
                Logger::error($e->getMessage());
                //try to cancel the order cancel
                try{
                    $checkoutManager->cancelStartedOrderPayment();
                }catch (\Exception $e){
                    //
                    Logger::error($e->getMessage());
                }
                //no specific handling
            }
            //something went wrong
            return $this->json(['status'=> 'ERROR' , 'message' => isset($response['customermessage']) ? $response['customermessage'] : $this->get('translator')->trans('payment.error.occured')]);

        } else {
            return $this->json(['status'=> 'ERROR' ,'message' => 'invalid payment type']);
        }
    }
    /**
     * payment complete handling - handles following things
     *  - payment successfully > redirects to checkout complete page
     *  - payment error > prints out all the error messages and adds button for retrying the payment
     *  - waiting for server side payment confirmation request - reloads after 2 sec
     */
    public function completeAction(Request $request)
    {
        $order = \Pimcore\Model\DataObject\OnlineShopOrder::getById(base64_decode($request->get('id')));
        $session = $this->get("session");
        $session->set("last_order_id", $order->getId());
        $session->save();

        if($state = $request->get('state') === BsPayone::PAYMENT_RETURN_STATE_CANCEL){
            $cart = $this->getCart();
            $checkoutManager = Factory::getInstance()->getCheckoutManager($cart);

            try {
                $checkoutManager->cancelStartedOrderPayment();
            } catch (UnsupportedException $e) {
                //
            }
        }


        $this->view->order = $order;
    }
    /**
     * checking action for payment via invoice
     */
    public function validateInvoicePaymentAction(Request $request)
    {
        $translator = $this->get('translator');
        $date = new \DateTime($request->get('birthDay'));
        //TODO calculate age correctly
        $age = rand(5, 60);
        if ($age >= 18) {
            $data = [
                'ok' => true
            ];
        } else {
            $data = [
                'errors' => [
                    $translator->trans('payment.payone-seamless.invoice.age-error')
                ]
            ];
        }
        return $this->json($data);
    }


    public function pollReferenceReadyAction(Request $request)
    {
        if($request->isXmlHttpRequest()){
            $data = array('ready' => $this->registry->findTransactionAppointedForPayoneReference($request->get('ref')));
            return new JsonResponse($data);
        }

    }

}