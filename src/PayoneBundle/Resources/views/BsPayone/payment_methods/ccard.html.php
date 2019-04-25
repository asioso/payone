<?php ?>

<script type="text/javascript" src="https://secure.pay1.de/client-api/js/v1/payone_hosted_min.js"></script>
<script>
    $(document).ready(function () {
            var request, config;

            config = {
                fields: {
                    cardpan: {
                        selector: "cardpan",                 // put name of your div-container here
                        type: "text",                        // text (default), password, tel
                        style: "font-size: 1em; border: 1px solid #000;"
                    },
                    cardcvc2: {
                        selector: "cvc2",                    // put name of your div-container here
                        type: "password",                    // select(default), text, password, tel
                        style: "font-size: 1em; border: 1px solid #000;",
                        size: "4",
                        maxlength: "4"
                    },
                    cardexpiremonth: {
                        selector: "cardexpiremonth",         // put name of your div-container here
                        type: "expired",                      // select(default), text, password, tel
                        size: "2",
                        maxlength: "2",
                        iframe: {
                            width: "50px"
                        }
                    },
                    cardexpireyear: {
                        selector: "cardexpireyear",          // put name of your div-container here
                        type: "select",                      // select(default), text, password, tel
                        iframe: {
                            width: "80px"
                        }
                    }
                },
                defaultStyle: {
                    input: "font-size: 1em; border: 1px solid #000; width: 175px;",
                    select: "font-size: 1em; border: 1px solid #000;",
                    iframe: {
                        width: "180px"
                    }
                },
                error: "errorOutput",                        // area to display error-messages (optional)
                language: Payone.ClientApi.Language.de       // Language to display error-messages
                                                             // (default: Payone.ClientApi.Language.en)
            };
            request = {
                request: '<?= $fields['request']; ?>',                  // fixed value
                responsetype: '<?= $fields['responsetype']; ?>',                        // fixed value
                mode: '<?= $fields['mode']; ?>',                                // desired mode
                mid: '<?= $fields['mid']; ?>',                                // your MID
                aid: '<?= $fields['aid']; ?>',                                // your AID
                portalid: '<?= $fields['portalid']; ?>',                         // your PortalId
                encoding: '<?= $fields['encoding']; ?>',                           // desired encoding
                storecarddata: '<?= $fields['storecarddata']; ?>',                        // fixed value
                hash: '<?= $fields['hash']; ?>'
            };

            var iframes = new Payone.ClientApi.HostedIFrames(config, request);
            iframes.setCardType("V");

            document.getElementById('cardtype').onchange = function () {
                iframes.setCardType(this.value);              // on change: set new type of credit card to process
            };

            /**
             * validates forms incl. payone iframe inputs
             */
            function check() {                               // Function called by submitting PAY-button
                //$('#loading').show();

                if (iframes.isComplete() === true) {
                    iframes.creditCardCheck('checkCallback');
                } else {
                    $('#ccError').append("<p><?= $this->translate('payment.payone-seamless.pay.validation-message-empty') ?></p>");
                    console.log("not complete");
                }
                $('#loading').hide();
                //$('#paymentsubmit').show();
            }


            $('#paymentsubmit').click(function (e) {
                $('#ccError').empty();
                e.preventDefault();
                $('#loading').show();
                check();
            });
        }
    );


    /**
     * callback for payone iframe check
     * @param response
     */
    function checkCallback(response) {

        if (response.status === "VALID") {
            //$('#paymentsubmit').show();
            document.getElementById("pseudocardpan").value = response.pseudocardpan;
            document.getElementById("truncatedcardpan").value = response.truncatedcardpan;
            startCCPayment();
        }
        else {
            //console.log(response);
            //TODO SHOW ERROR MESSAGES!
            $('#loading').hide();
            $('#ccError').after("<p><?= $this->translate('payment .payone-seamless.pay.validation-message-check') ?></p>")
        }

    }


    function startCCPayment() {
        var form = $('#ccform');
        var container = form.closest('.payment-accordion');
        var serializedForm = form.serialize();

        SeamlessHandler.startPayment(container, serializedForm);
    }

</script>
<div class="panel-body">
    <div class="checkout-dropdown payone-checkout-dropdown">
        <form name="paymentform" id="ccform" action="" method="post">
            <fieldset>
                <input type="hidden" class="payment-type" name="paymentType" value="CCARD">
                <input type="hidden" name="pseudocardpan" id="pseudocardpan">
                <input type="hidden" name="paymentType" id="paymentType" value="CCARD">
                <input type="hidden" name="truncatedcardpan" id="truncatedcardpan">
                <input type="hidden" name="cardtypeResponse" id="cardtyperesponse">
                <input type="hidden" name="cardexpiredResponse" id="cardexpiredResponse">

                <!-- <img style="border: #FFF solid 5px; width: 100px;" src="static/visa.png" alt="" id="visa" /> <img src="static/mastercard.png" alt="" id="mastercard" style="border: #FFF solid 5px; width: 100px;" /><br /> -->
                <div id="ccError">

                </div>
                <!-- configure your cardtype-selection here -->
                <label for="cardtypeInput"  style="width: 100px; height: 30px; line-height: 30px;" ><?= $this->translate('payment.payone-seamless.pay.card-type') ?>:</label>
                <select id="cardtype" style="border: 1px solid rgb(0, 0, 0);" >
                    <option value="V">VISA</option>
                    <option value="M">Mastercard</option>
                </select>
                <div style="height: 30px; overflow: hidden;" >
                    <label for="cardpanInput" style="width: 100px; height: 30px; line-height: 30px; vertical-align: top; margin-top: -2px;"><?= $this->translate('payment.payone-seamless.pay.cardpan') ?>:</label>
                    <span class="inputIframe" id="cardpan" style="display: inline-block; width: 200px;" ><div name="cardpan"></div></span>
                </div>
                <div name="cvc" style="height: 30px; overflow: hidden;" >
                    <label for="cvcInput" style="width: 100px; height: 30px; line-height: 30px; vertical-align: top; margin-top: -2px;" ><?= $this->translate('payment.payone-seamless.pay.now.cvc') ?>:</label>
                    <span class="inputIframe" id="cvc2" style="width: 200px;" ></span>
                </div>

                <div name="expired" style="height: 30px; overflow: hidden;" >
                    <label for="expireInput" style="width: 100px; height: 30px; line-height: 30px; vertical-align: top; margin-top: -2px;" ><?= $this->translate('payment.payone-seamless.pay.expiration-date') ?>:</label>
                    <span id="expireInput" class="inputIframe" style="width: 200px;" >
                <span id="cardexpiremonth"></span>
                <span id="cardexpireyear"></span>
            </span>
                </div>
                <div name="name">
                    <label for="firstname" style="width: 100px; height: 30px; line-height: 30px;" ><?= $this->translate('payment.payone-seamless.firstname') ?>:</label>
                    <input id="firstname" type="text" name="firstname" value="" style="width: 200px; border: 1px solid rgb(0, 0, 0);" >
                    <label for="lastname" style="width: 100px; height: 30px; line-height: 30px;" ><?= $this->translate('payment.payone-seamless.pay.lastname') ?>:</label>
                    <input id="lastname" type="text" name="lastname" value="" style="width: 200px; border: 1px solid rgb(0, 0, 0);" >
                </div>

                <div id="errorOutput"></div>
                <div>
                    <div id="loading" style="display: none;"></div>
                    <input id="paymentsubmit" type="button"
                           value="<?= $this->translate('payment.payone-seamless.pay.now') ?>"
                           class="btn btn-primary-red">

                </div>
            </fieldset>
        </form>
        <div id="paymentform"></div>
    </div>
</div>
