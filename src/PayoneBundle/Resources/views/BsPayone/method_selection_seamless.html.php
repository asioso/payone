<?php
/**
 * @var \Pimcore\Templating\PhpEngine $this
 * @var \Pimcore\Templating\PhpEngine $view
 * @var \Pimcore\Templating\GlobalVariables $app
 */
?>

<link rel="stylesheet" href="/static/css/payone-checkout.css" type="text/css"/>

<script src="<?= "";//$this->javascriptUrl; ?>"></script>

<script src="/static/plugins/bootstrap/js/bootstrap.min.js"></script>
<script src="https://cdn.jsdelivr.net/jquery.validation/1.16.0/jquery.validate.min.js"></script>

<script src="<?=$this->payoneFrontendScript?>"></script>
<style>
    .btn-primary-red {
        background-color: #c92f05;
        border-color: #c92f05;
        border-radius: 0;
        color: #fff;
    }
    .btn-primary-red:hover{
        color: #fff;
        background-color: #979797;
        border-color: #979797;
        transition: all 0.4s ease-in-out 0s;
        -webkit-transition: all 0.4s ease-in-out 0s;
        -ms-transition: all 0.4s ease-in-out 0s;
    }



    .payment-text{
        color: #444444
    }
    .payment-text:hover{
        color: #979797;
        transition: all 0.4s ease-in-out 0s;
        -webkit-transition: all 0.4s ease-in-out 0s;
        -ms-transition: all 0.4s ease-in-out 0s;
    }
</style>
<div id="errormessage">
</div>
<div id="loading" style="display: none;" class="payone-seamless-loading ajax-loader"></div>
<div id="mandateOption" style="display: none;" >
    <div id="mandateText"></div>
    <a id="mandateConfirm" class="btn btn-primary-red mandate-ok"><?= $this->translate('payment.payone-seamless.mandate.confirm') ?></a>
    <a id="mandateCancel" class="btn btn-primary mandate-cancel"><?= $this->translate('payment.payone-seamless.mandate.cancel') ?></a>
</div>

<div class="panel-group payment-accordion"

     data-generate-redirect-url="<?= $_SERVER["REQUEST_SCHEME"] ?>://<?=$_SERVER["HTTP_HOST"]?>/<?= ltrim( $this->pimcoreUrl(['controller' => "BsPayone",'action'=>'get-payone-redirect-url'],'payone',true),'/')?>"
     data-check-url="<?= $_SERVER["REQUEST_SCHEME"] ?>://<?=$_SERVER["HTTP_HOST"]?>/<?= ltrim( $this->pimcoreUrl(['controller' => "BsPayone",'action'=>'check-mandate-status'],'payone',true),'/')?>"
     id="checkout-accordion" role="tablist" aria-multiselectable="true">
    <?php foreach ($this->paymentMethods as $paymentMethod => $options) {
        if (is_array($this->config['disabledPaymentMethods']) and in_array($paymentMethod, $this->config['disabledPaymentMethods'])) {
            continue;
        }
        ?>
        <div class="panel panel-default">
            <a role="button" data-toggle="collapse" data-parent="#checkout-accordion"
               href="#collapse<?= $paymentMethod ?>" aria-controls="collapse<?= $paymentMethod ?>" aria-expanded="false"
               class="collapsed">
                <div class="panel-heading" role="tab">
                    <div class=" row">
                        <div class="col col-sm-3">
                            <?php if ($icon = $options['icon']) { ?>
                                <img src="<?= $icon ?>" width="100" class="payone-payment-icon"/>
                            <?php } ?>
                        </div>
                        <div class="col col-sm-9 payment-text" >
                            <h4 class="panel-title">
                                <?= $this->translate('payment.payone-seamless.payment-method.' . $paymentMethod) ?>
                            </h4>
                            <p><?= $this->translate('payment.payone-seamless.payment-method.description.' . $paymentMethod) ?></p>
                        </div>
                    </div>
                </div>
            </a>

            <div id="collapse<?= $paymentMethod ?>" class="panel-collapseN collapse" role="tabpanel">
                <?php if ($options['partial']) { ?>
                    <?= $this->template($options['partial'], $fields) ?>
                <?php } else { ?>
                    <div class="panel-body">
                        <div class="checkout-dropdown payone-checkout-dropdown">
                            <form>
                                <input type="hidden" class="payment-type" name="paymentType"
                                       value="<?= $paymentMethod ?>"/>
                                <a class="btn btn-primary-red js-payone-payment-submit"><?= $this->translate('payment.payone-seamless.pay-now.' . $paymentMethod) ?></a>
                            </form>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    <?php } ?>

</div>