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
use Pimcore\Bundle\AdminBundle\HttpFoundation\JsonResponse;
use Pimcore\Bundle\EcommerceFrameworkBundle\Exception\UnsupportedException;
use Pimcore\Bundle\EcommerceFrameworkBundle\Factory;
use Pimcore\Document\Tag\Exception\NotFoundException;
use Pimcore\Logger;
use Pimcore\Tool;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class BsPayoneController
 * @package PayoneBundle\Controller
 */
class BsPayoneController extends AbstractCartAware
{

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
        $paymentProvider = Factory::getInstance()->getPaymentManager()->getProvider('payone');

        if ($committedOrder = $commitOrderProcessor->committedOrderWithSamePaymentExists($params, $paymentProvider)) {
            Logger::info('Order with same payment is already committed, doing nothing. OrderId is ' . $committedOrder->getId());
        } else {
            $order = $commitOrderProcessor->handlePaymentResponseAndCommitOrderPayment($params, $paymentProvider);
            Logger::info('Finished server side call. OrderId is ' . $order->getId());
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
            $config = [
                'successURL' => $request->getSchemeAndHttpHost() . $this->generateUrl('payone', ['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                        'state' => BsPayone::PAYMENT_RETURN_STATE_SUCCESS, 'prefix' => $language]),
                'failureURL' => $request->getSchemeAndHttpHost() . $this->generateUrl('payone', ['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                        'state' => BsPayone::PAYMENT_RETURN_STATE_FAILURE, 'prefix' => $language]),
                'cancelURL' => $request->getSchemeAndHttpHost() . $this->generateUrl('payone', ['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                        'state' => BsPayone::PAYMENT_RETURN_STATE_CANCEL, 'prefix' => $language]),
                'serviceURL' => Tool::getHostUrl(),
                'pendingURL' => $request->getSchemeAndHttpHost() . $this->generateUrl('payone', ['action' => 'complete', 'id' => base64_encode($paymentInformation->getObject()->getId()),
                        'state' => BsPayone::PAYMENT_RETURN_STATE_PENDING, 'prefix' => $language]),
                'confirmURL' => $request->getSchemeAndHttpHost() . $this->generateUrl('payone', ['action' => 'confirm-payment-server-side', 'elementsclientauth' => 'disabled']),
                'completedURL' => $request->getSchemeAndHttpHost() . $this->generateUrl('payone', ['action' => 'completed', 'controller'=> 'checkout', 'id' => $order->getId(), 'prefix' => $language]),
                    #$this->pimcoreUrl(['action' => 'completed', 'controller' => 'checkout', 'id' => $this->order->getId(),'prefix'=>$this->language], 'action', true)
                'paymentInfo' => $paymentInformation,
                'paymentType' => $paymentType,
                'cart' => $this->getCart(),
                'birthday' => $request->get('birth'),
                'orderDescription' => $orderNumber,
                'reference' => $orderNumber,
                'lang' => 'de',
                'pseudocardpan' => $request->get('pseudocardpan'),
                'truncatedcardpan' => $request->get('truncatedcardpan'),
                'cardexpiredResponse' => $request->get('cardexpiredResponse'),
                'iban' => $request->get('iban'),
                'bic' => $request->get('bic'),
                'mandate_identification' => $request->get('mandate'),
                'bankCountry' => $request->get('bankCountry'),
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
                //try to cancel the order cancel
                try{
                    $checkoutManager->cancelStartedOrderPayment();
                }catch (\Exception $e){
                    //
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

}