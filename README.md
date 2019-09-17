# PayOne Payment Bundle


## Prerequisites
* PHP 7.1 or higher (https://secure.php.net/)
* Composer (https://getcomposer.org/download/)
* A Pimcore  Installation using the **pimcore e-commerce framework** (v5.7 or higher)
* A PAYONE account or test account (https://www.payone.com/kontakt/)



![payment_frame_screenshot][frame]         

## Installation

### composer
add the bundle to composer.json with

```
composer require asioso/pimcore-payone-module

```


### Enable the Bundle in the Extension Manager

[NOTE] make sure, that you have the NumberGeneratorBundle enabled.

otherwise same old same old - this will create a new database Table and import an ObjectBrick.


### Add Static Routes for the Bundle

you need to add a static route named **payone** in pimcore's backend. Otherwise the checkout process will not work


```
pattern:
#(.*?)/paymentAction/([a-zA-Z\-\_]+)/([a-z\-]+)#


reverse:
/%prefix/paymentAction/%controller/%action

bundle:
PayoneBundle

controller:
%controller

action:
%action

variables:
prefix,controller,action

```


![static_routes_screenshot][route]         



## Configuration

add payone as payment provider in your e-commerce configuration. Use payone as payment provider in your checkout manager: 

```
            default:
                payment:
                    provider: payone
```

see an example below:

```
# Configuration of payment providers, key is name of provider
providers:
    payone:
      provider_id: PayoneBundle\Ecommerce\PaymentManager\BsPayone
      profile: sandbox
      profiles:
        _defaults:
          data_processor: \AppBundle\Ecommerce\DataProcessor
          hash_algorithm: md5
          #paypal_activate_item_level: true
          partial: PayoneBundle:BsPayone:method_selection_seamless.html.php
          js: /bundles/payone/js/payone-frontend.js
          iframe_css_url: /bundles/payone/css/payment-iframe.css?elementsclientauth=disabled
          payment_methods:
            SEPA:
              icon: /static/img/payment/icons/sepa.png
              partial: PayoneBundle:BsPayone:payment_methods/sepa.html.php

            CCARD:
              icon: /static/img/payment/icons/cc.png
              partial: PayoneBundle:BsPayone:payment_methods/ccard.html.php

            PAYPAL:
              icon: /static/img/payment/icons/paypal.png

            PAYDIRECT:
              icon: /static/img/payment/icons/paydirect.png
              #partial: PayoneBundle:BsPayone:payment_methods/paydirect.html.php

            SOFORT:
              icon: /static/img/payment/icons/klarna.svg
              #partial: PayoneBundle:BsPayone:payment_methods/sofort.html.php

            GIROPAY:
              icon: /static/img/payment/icons/giropay.png
              partial: PayoneBundle:BsPayone:payment_methods/giropay.html.php


            INVOICE:
              icon: /static/img/payment/icons/invoice-logo.png
              # example: use alternative credentials for invoices
              aid: <aid_other-sandbox>
              mid: <mid_other-sandbox>
              portalid: <other-portal-id>
              key: <other-key>
              mode: '%env(PAYONE_MODE)%' #example use env file to set your configuration see- /examples/payment.env



        sandbox:
          aid: <aid_sandbox>
          mid: <mid_sandbox>
          portalid: <portalId>
          key: <sandbox-key>
          mode: test

        live:
          aid: <aid_sandbox>
          mid: <mid_sandbox>
          portalid: <portalId>
          key: <sandbox-key>
          mode: live
```

#### Enable Payment Provider

update your payment controller to support payone as payment provider. take a look at  [this controller example](https://github.com/asioso/payone/blob/master/examples/Controller/PaymentController.php)
Below you can see the most crucial part in the payment frame action:


```php

    /**
     * payment iframe
     */
    public function paymentFrameAction(Request $request)
    {
        // init
        $cart = $this->getCart();
        $checkoutManager = Factory::getInstance()->getCheckoutManager($cart);

        if ($checkoutManager->isCommitted()) {
            throw new \Exception('Cart already committed');
        }

        $paymentInformation = $checkoutManager->startOrderPayment();
        $payment = $checkoutManager->getPayment();

        $language = substr($request->getLocale(), 0, 2);

        // payment config
        if($payment instanceof BsPayone) {
            // payone
            $payment->setPaymentInformation($paymentInformation);
            $config['orderIdent'] = $paymentInformation->getInternalPaymentId();
            $config['cart'] = $cart;
            $config['language'] =   substr($request->getLocale(), 0, 2);

        }

        else {
            throw new \Exception('Unknown Payment configured.');
        }

        // init payment
        $this->view->payment = $payment->initPayment($cart->getPriceCalculator()->getGrandTotal(), $config);        
        
    }
    

```



### DataProcessor

We need some information from earlier checkout steps, so your implementation of this class will be used to fill in personal Data and shipping data.
Take a look at [this example](https://github.com/asioso/payone/blob/master/examples/DataProcessor/DataProcessor.php)



### Callback url

you need to register your application's callback address in payone's merchant service portal.
it uses the the static route you defined earlier, and will look like this:

```
https://<your.domain>/de/paymentAction/BsPayone/confirm-payment-server-side

```


### Overriding default Payment URLs
In case you don't want to rely on the generated success/pending/failure URLs provided by this bundle (based on static routes- see above), there is another way using symfony's Dependency Injection component.

Register a new CompilerPass, take a look at [this example](https://github.com/asioso/payone/blob/master/examples/DependencyInjection/PaymentUrlGeneratorCompilerPass.php), which sets a tailored generator service that generates a custom completed-route, but still relies on the default implementation for the remaining URLs.
see [this class](https://github.com/asioso/payone/blob/master/examples/UrlGenerator/MyPaymentUrlGenerator.php) for more details. 


### Any Questsion

write to info@asioso.com


[route]: https://github.com/asioso/payone/raw/master/documentation/img/static_routes.png "Extension Manager"
[frame]: https://github.com/asioso/payone/raw/master/documentation/img/payment_frame.png "Payment Frame"
