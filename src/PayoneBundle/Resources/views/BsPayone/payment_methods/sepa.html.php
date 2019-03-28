<?php ?>
<script>
    $(document).ready(function () {
            $('#sepasubmit').click(function (e) {
                e.preventDefault();
                var form = $('#sepaform');
                var container = form.closest('.payment-accordion');
                var serializedForm = form.serialize();
                form.validate();
                if (form.valid()) {
                    container.hide();
                    $('#loading').show();
                    SeamlessHandler.checkMandate(container, serializedForm).then(function (response) {
                        $("#mandate").val( response.mandate);


                        if (response.status === "pending") {
                            $('#loading').hide();
                            //display mandate text!
                            $('#mandateText').html(response.mandate_text);

                            $('#mandateConfirm').click(function (e) {
                                $('#mandateOption').hide();
                                $('#loading').show();
                                form = $('#sepaform');
                                serializedForm = form.serialize();
                                SeamlessHandler.startPayment(container, serializedForm);
                            });

                            $('#mandateCancel').click(function (e) {
                                top.window.location.reload();
                            });
                            $('#mandateOption').show();

                        } else if (response.status === "active") {
                            form = $('#sepaform');
                            serializedForm = form.serialize();
                            SeamlessHandler.startPayment(container, serializedForm);
                        }
                    });
                }
            });
        }
    );


</script>


<div class="panel-body">
    <div class="checkout-dropdown payone-checkout-dropdown">
        <form name="sepaform" id="sepaform" action="" method="post">
            <fieldset>
                <div class="alert alert-danger invoice-checkbox-error payone-checkbox-error" style="display:none;">
                    <?= $this->translate('payment.payone-seamless.checkbox-not-checked') ?>
                </div>

                <input type="hidden" class="payment-type" name="paymentType" value="SEPA"/>
                <input type="hidden" class="mandate" id="mandate" name="mandate">

                <div class="row">
                    <div class="col-sm-12">
                        <label for="iban">IBAN:</label>
                        <input id="iban" type="text" name="iban" value="" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <label for="bic">BIC:</label>
                        <input id="bic" type="text" name="bic" value="" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <input id="sepasubmit" type="button"
                               value="<?= $this->translate('payment.payone-seamless.pay.now') ?>"
                               class="btn btn-primary-red">
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
</div>
