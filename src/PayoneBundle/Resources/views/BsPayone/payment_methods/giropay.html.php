<?php ?>

<script>
    $(document).ready(function () {
            $('#giropayformsubmit').click(function (e) {
                e.preventDefault();
                startPayment();
            });
        }
    );


    function startPayment() {
        var form = $('#giropayform');
        var container = form.closest('.payment-accordion');
        var serializedForm = form.serialize();
        console.log(serializedForm);
        if(serializedForm.length > 0){
            SeamlessHandler.startPayment(container, serializedForm);
        }


    }

</script>
<div class="panel-body">
    <div class="checkout-dropdown payone-checkout-dropdown">
        <form name="giropaymentform" id="giropayform" action="" method="post">
            <fieldset>
                <input type="hidden" class="payment-type" name="paymentType" value="GIROPAY">
                <div class="alert alert-danger invoice-checkbox-error payone-checkbox-error" style="display:none;">
                    <?= $this->translate('payment.payone-seamless.checkbox-not-checked') ?>
                </div>


                <div class="row">
                    <div class="col-sm-12">
                        <label for="bankCountry"><?= $this->translate('payment.payone-seamless.giropay.bankCountry',[], 'PayoneBundle') ?> </label>
                        <select id="bankCountry" name="bankCountry">
                            <option value="DE" selected><?= $this->translate('payment.country.germany',[], 'PayoneBundle') ?></option>
                            <option value="AT" ><?= $this->translate('payment.country.austria',[], 'PayoneBundle') ?></option>
                            <option value="CH"><?= $this->translate('payment.country.switzerland',[], 'PayoneBundle') ?></option>
                            <option value="NL"><?= $this->translate('payment.country.netherlands',[], 'PayoneBundle') ?></option>
                            <option value="PL"><?= $this->translate('payment.country.poland',[], 'PayoneBundle') ?></option>
                        </select>
                    </div>
                </div>


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
                        <input id="giropayformsubmit" type="button" value="<?= $this->translate('payment.payone-seamless.pay.now',[], 'PayoneBundle') ?>" class="btn btn-primary-red js-payone-payment-submit" >
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
</div>
