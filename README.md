#PayOne Payment Bundle



##Installation

###composer
in your composer.json file add the following repo under your

```json
"repositories": [
    {
      "type": "vcs",
      "url":  "git@bitbucket.org:asioso/payone.git"
    }
  ],
``` 

after that make sure you have access to the repo and added your ssh key to your bitbucket account.
test if composer can find the package.

```
composer search asioso

>>>>>
asioso/payone A bundle to help with payone  

```

add the bundle to composer.json with

```
composer require asioso/payone:dev-master

```

tadaaa

###Enable the Bundle in the Extension Manager

[NOTE] make sure, that you have the NumberGeneratorBundle enabled.

otherwise same old same old - this will create a new database Table and import an ObjectBrick.



##Configuration
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


###DataProcessor
We need some information from earlier checkout steps, so your implementation of this class will be used to fill in personal Data and shipping data.
Take a look at [this example](https://bitbucket.org/asioso/payone/src/master/examples/DataProcessor/DataProcessor.php)
