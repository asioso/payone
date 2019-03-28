<?php ?>

<script>
    $(document).ready(function () {
            $('#paymentsubmit').click(function (e) {
                e.preventDefault();
                console.log("click");
                $('#loading').show();
                startPayment();
            });
        }
    );


    function startPayment() {
        var form = $('#paydirectform');
        var container = form.closest('.payment-accordion');
        var serializedForm = form.serialize();
        console.log(serializedForm);
        console.log(container);

        SeamlessHandler.startPayment(container, serializedForm);
    }

</script>
<div class="panel-body">
    <div class="checkout-dropdown payone-checkout-dropdown">
        <form name="paymentform" id="paydirectform" action="" method="post">
            <fieldset>
                <input type="hidden" class="payment-type" name="paymentType" value="PAYDIRECT">
                <div class="alert alert-danger invoice-checkbox-error payone-checkbox-error" style="display:none;">
                    <?= $this->translate('payment.payone-seamless.checkbox-not-checked') ?>
                </div>

                <input type="hidden" class="payment-type" name="paymentType" value="SOFORT"/>

                <select>
                    <option value="DE" selected><?= $this->translate('Germany') ?></option>
                    <option value="AT"><?= $this->translate('Austria') ?></option>
                    <option value="CH"><?= $this->translate('Switzerland') ?></option>
                    <option value="NL"><?= $this->translate('Netherlands') ?></option>
                </select>


                <div class="row">
                    <div class="col-sm-12">
                        <label for="iban">IBAN:</label>
                        <input id="iban" type="text" name="iban" value="">
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <label for="bic">BIC:</label>
                        <input id="bic" type="text" name="bic" value="">
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <input id="paymentsubmit" type="button" value="<?= $this->translate('payment.payone-seamless.pay.now') ?>" class="btn btn-primary js-payone-payment-submit" >
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
</div>
