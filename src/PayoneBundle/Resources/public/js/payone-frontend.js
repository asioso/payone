

var SeamlessHandler = {
    pendingOps: null,
    unloadListener : null,

    init: function () {
        this.initEvents();
        this.pendingOps = new Set();
        var message = SeamlessHandler.popErrorMessage();
        if ('undefined' !== typeof message && message !== null) {
            $('#errormessage').append("<div class=\"alert alert-danger\">" + "<strong>"+message+"</strong>" + "</div>");
        }
    },

    initEvents: function () {
        var that = this;
        that.unloadListener = function(event){
            if (that.pendingOps.size) {
                event.returnValue = 'There is pending work. Sure you want to leave?';
            }
        };

        $('.js-payone-payment-submit').on('click', function (e) {
            e.preventDefault();
            let form = $(this).closest('form');
            let container = form.closest('.payment-accordion');
            let paymentType = form.find(".payment-type").val();
            let serializedForm = form.serialize();


            SeamlessHandler.startPayment(container, serializedForm);

        });

        top.window.addEventListener('beforeunload', that.unloadListener);

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

    addToPendingWork: function(promise) {
        var that= this;
        return new Promise(function(resolve, reject){
            that.pendingOps.add(promise);
            var cleanup = () => that.pendingOps.delete(promise);
            promise.then(cleanup).catch(cleanup);
            resolve();
        });

    },

    startPayment: function (container, serializedForm) {
        var that = this;
        $('.payone-error').hide();
        $('.payone-checkbox-error').hide();
        container.html(SeamlessHandler.getLoadingDiv());
        SeamlessHandler.scrollTo(container);

        that.addToPendingWork( $.get(container.data('generate-redirect-url') + '?' + serializedForm)
            .then((function (response) {
                console.log(response);
                console.log('right one');
                top.window.removeEventListener("beforeunload", that.unloadListener);
                if (response.status === "REDIRECT" && response.url) {
                    top.window.location.href = response.url;
                }

                if (response.status === "ERROR") {
                    SeamlessHandler.pushErrorMessage(response.message);
                    top.window.location.reload();
                }
            }))
        )
    },


    getLoadingDiv: function () {
        return '<div class="payone-seamless-loading ajax-loader"></div>';
    },

    scrollTo: function (element) {
        console.log(element);
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