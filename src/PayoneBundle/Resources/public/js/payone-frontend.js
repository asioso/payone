

var SeamlessHandler = {
    init: function () {
        this.initEvents();
        var message = SeamlessHandler.popErrorMessage();
        if ('undefined' !== typeof message && message !== null) {
            $('#errormessage').append("<div class=\"alert alert-danger\">" + "<strong>"+message+"</strong>" + "</div>");
        }
    },

    initEvents: function () {
        $('.js-payone-payment-submit').on('click', function (e) {
            e.preventDefault();
            let form = $(this).closest('form');
            let container = form.closest('.payment-accordion');
            let paymentType = form.find(".payment-type").val();
            let serializedForm = form.serialize();


            SeamlessHandler.startPayment(container, serializedForm);

        });

        $('.panel-default').click(function(){
            $('#errormessage').empty();
        });


    },

    checkMandate: function(container, serializedForm){
      return new Promise(function(resolve, reject){

          $.get(container.data('check-url') + '?' + serializedForm)
              .then((function (response) {
                  resolve(response);
              }));
      });
    },


    startPayment: function (container, serializedForm) {

        $('.payone-error').hide();
        $('.payone-checkbox-error').hide();
        container.html(SeamlessHandler.getLoadingDiv());
        SeamlessHandler.scrollTo(container);

        $.get(container.data('generate-redirect-url') + '?' + serializedForm)
            .then((function (response) {
                if (response.status === "REDIRECT" && response.url) {
                    top.window.location.href = response.url;
                }

                if (response.status === "ERROR") {
                    SeamlessHandler.pushErrorMessage(response.message);
                    top.window.location.reload();
                }
            }));
    },


    getLoadingDiv: function () {
        return '<div class="payone-seamless-loading ajax-loader"></div>';
    },

    scrollTo: function (element) {
        $('html, body').animate({
            scrollTop: element.offset().top - 100
        }, 500);
    },
    pushErrorMessage: function(message){
        if (SeamlessHandler.storageAvailable('sessionStorage')) {
            sessionStorage.setItem("neyer.error", message);
        }
        else {
            // Too bad, no localStorage for us
        }
    },
    popErrorMessage: function(){
        if (SeamlessHandler.storageAvailable('sessionStorage')) {
            let value = sessionStorage.getItem("neyer.error");
            sessionStorage.removeItem("neyer.error");
            return value;
        }
        else {
            // Too bad, no localStorage for us
        }
        return null;
    },
    storageAvailable:function (type) {
    try {
        let storage = window[type],
            x = '__storage_test__';
        storage.setItem(x, x);
        storage.removeItem(x);
        return true;
    }
    catch(e) {
        return e instanceof DOMException && (
                // everything except Firefox
            e.code === 22 ||
            // Firefox
            e.code === 1014 ||
            // test name field too, because code might not be present
            // everything except Firefox
            e.name === 'QuotaExceededError' ||
            // Firefox
            e.name === 'NS_ERROR_DOM_QUOTA_REACHED') &&
            // acknowledge QuotaExceededError only if there's something already stored
            storage.length !== 0;
    }
}

};

$(function () {
    SeamlessHandler.init();
});

(function ($) {
    $.fn.serializeObject = function () {
        "use strict";

        let result = {};
        let extend = function (i, element) {
            let node = result[element.name];

            // If node with same name exists already, need to convert it to an array as it
            // is a multi-value field (i.e., checkboxes)

            if ('undefined' !== typeof node && node !== null) {
                if ($.isArray(node)) {
                    node.push(element.value);
                } else {
                    result[element.name] = [node, element.value];
                }
            } else {
                result[element.name] = element.value;
            }
        };

        $.each(this.serializeArray(), extend);
        return result;
    };
})(jQuery);