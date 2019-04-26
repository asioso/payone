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
        var form = $('#sofortform');
        var container = form.closest('.payment-accordion');
        var serializedForm = form.serialize();
        console.log(serializedForm);
        console.log(container);

        SeamlessHandler.startPayment(container, serializedForm);
    }

</script>
<div class="panel-body">
    <div class="checkout-dropdown payone-checkout-dropdown">
        <form name="paymentform" id="sofortform" action="" method="post">
            <fieldset>
                <input type="hidden" class="payment-type" name="paymentType" value="SOFORT">
                <div class="alert alert-danger invoice-checkbox-error payone-checkbox-error" style="display:none;">
                    <?= $this->translate('payment.payone-seamless.checkbox-not-checked',[], 'PayoneBundle') ?>
                </div>

                <input type="hidden" class="payment-type" name="paymentType" value="SOFORT"/>

                <div>
                    <label for="bankcountry"><?= $this->translate('payment.bankcountry',[], 'PayoneBundle') ?></label>
                    <select id="bankcountry">
                        <option value="DE" selected><?= $this->translate('payment.germany',[], 'PayoneBundle') ?></option>
                        <option value="AT"><?= $this->translate('payment.austria',[], 'PayoneBundle') ?></option>
                        <option value="CH"><?= $this->translate('payment.switzerland',[], 'PayoneBundle') ?></option>
                    </select>
                </div>

                <div>

                    <label for="iban">IBAN:</label>
                    <input id="iban" type="text" name="iban" value="" autocomplete="off">

                </div>

                <div>
                    <label for="bic">BIC:</label>
                    <input id="bic" type="text" name="bic" value="" autocomplete="off">

                </div>
                <div>

                    <input id="paymentsubmit" type="button"
                           value="<?= $this->translate('payment.payone-seamless.pay.now') ?>"
                           class="btn btn-primary-red js-payone-payment-submit">

                </div>
            </fieldset>
        </form>
    </div>
</div>
