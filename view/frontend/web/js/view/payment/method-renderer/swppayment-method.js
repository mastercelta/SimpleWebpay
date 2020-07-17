/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'Magento_Ui/js/modal/alert',
        'jquery',
        'porthole',
        'simplewebpay',
        'viewprocess',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Checkout/js/action/redirect-on-success',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/model/error-processor',
        'mage/url'
    ],
    function (alert, $, porthole, simplewebpay,viewprocess,
        quote,
        Component,
        additionalValidators,
        redirectOnSuccessAction,
        fullScreenLoader,
        errorProcessor,
        urlBuilder
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Cenpos_SimpleWebpay/payment/swppayment',
                webpaytokenid: ''
            },
            initObservable: function () {
                this._super()
                    .observe([
                        'webpaytokenid'
                    ]);
                return this;
            },
            /** Returns send check to info */

            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },

            getSimpleForm: function () {
                var self = this;
                self.webpayinit = false;
                self.createWebpay();
               return window.checkoutConfig.payment.swppayment.dataConfig;
            },

            createWebpay:function(){
                $('.payment-method-result-webpay').html("");
                $("#NewCenposPlugin").show();
                $("#SubmitWebpay").show();
                $("#SubmitWebpaySend").hide();
                $("#NewCenposPlugin").html("<div></div>");
                var isToken = window.checkoutConfig.payment.swppayment.usetoken == "true";
                var self = this;
                try{
                    $.ajax({
                        type: "POST",
                        url: window.checkoutConfig.payment.swppayment.urlsession,
                        beforeSend: function () {
                            $(".payment-method-result-webpay").append("<div id='loadersavecard' style='background-color: rgba(255,255,255,0.5);width:100%;position: relative;z-index: 100;top: 0;height: 130px;margin-top: -120px;'><img style='display: block;margin: 28px 0 0 71px;float: left;' src='" + window.checkoutConfig.payment.swppayment.urlimage + "' /></div>");
                        },
                        success: function (msg2) {
                            msg2 = $.parseJSON(msg2);
                            self.wascreate = false;
                            $("#loadersavecard").remove();
                            if (msg2.Result == 0) {
                                var params = "";
                                params += "verifyingpost=" + encodeURIComponent(msg2.Data);
                                params += "&isemail=true";
                                params += "&iscvv="+window.checkoutConfig.payment.swppayment.iscvv;
                                if(window.checkoutConfig.payment.swppayment.istoken19 === "true") params += "&type=createtoken19";
                                if (quote.guestEmail !== "" && quote.guestEmail !== null && quote.guestEmail !== undefined) {
                                    params += "&email=" + quote.guestEmail;
                                    isToken = false;
                                }
                                
                                params += "&onlyform="+((isToken) ? "false" : "true");
                                    
                                $("#NewCenposPlugin > div").createWebpay(
                                    {
                                        url: window.checkoutConfig.payment.swppayment.url,
                                        params: params,
                                        width: "500",
                                        height: "340",
                                        sessionToken: true,
                                        success: function (msg) {
                                            if (msg !== "Error") {
                                                $("#NewCenposPlugin").hide();
                                                if (typeof (msg) !== "object") msg = $.parseJSON(msg);
                                                if (msg.RecurringSaleTokenId !== null && msg.RecurringSaleTokenId !== "" && msg.RecurringSaleTokenId !== undefined)
                                                    $("#FormWebpay").html("");
                                                for (var indice in msg) {
                                                    if (indice.toLowerCase() === "recurringsaletokenid") {
                                                        if (msg[indice].indexOf("CRYPTO") < 0  && isToken) isToken = false;
                                                    }
                                                    if (indice.toLowerCase() === "cardtype" && (window.checkoutConfig.payment.swppayment.istoken19 !== "true")) {
                                                        $(".payment-method-result-webpay").append("<strong>Card Type: </strong>" + msg[indice] + "<br />");
                                                    }
                                                    if (indice.toLowerCase() === "protectedcardnumber") {
                                                        $(".payment-method-result-webpay").append("<strong>Card Number: </strong>" + msg[indice] + "<br />");
                                                    }
                                                    if (indice.toLowerCase() === "cardexpirationdate") {
                                                        $(".payment-method-result-webpay").append("<strong>Expiration: </strong>" + msg[indice] + "<br />");
                                                    }
                                                    $("#FormWebpay").append('<input type="hidden" name="payment[webpay' + indice.toLowerCase() + ']" value="' + msg[indice] + '" />')
                                                    this[indice.toLowerCase()] = msg[indice];
                                                }
                
                                                if (isToken) {
                                                    $(".payment-method-result-webpay").append("<a id='SendTokenClick' style='display:block; cursor: pointer'>Save card</a>");
                                                    $("#SendTokenClick").click(function () {
                                                        $.ajax({
                                                            type: "POST",
                                                            url: window.checkoutConfig.payment.swppayment.urlsave,
                                                            data: msg,
                                                            beforeSend: function () {
                                                                $(".payment-method-result-webpay").append("<div id='loadersavecard' style='background-color: rgba(255,255,255,0.5);width:100%;position: relative;z-index: 100;top: 0;height: 130px;margin-top: -120px;'><img style='display: block;margin: 28px 0 0 71px;float: left;' src='" + window.checkoutConfig.payment.swppayment.urlimage + "' /></div>");
                                                            },
                                                            success: function (msg) {
                                                                $("#loadersavecard").remove();
                                                                msg = $.parseJSON(msg);
                                                                if (msg.Result == 0) {
                                                                    $("#SendTokenClick").hide();
                                                                } else {
                                                                    self.showalert("Error", msg.Message);
                                                                }
                                                            }
                                                        });
                                                    });
                                                }
                                                $(".payment-method-result-webpay").append("<a id='ChangeCard' style='display:block; cursor: pointer'>Select Another Card</a>");
                                                $("#ChangeCard").click(function(){self.createWebpay();});
                                                self.wascreate = true;
                                                $("#SubmitWebpay").hide();
                                                $("#SubmitWebpaySend").show();
                                            }
                                        },
                                        cancel: function (msg) {
                                           // var custommsg = {};
                                            if(window.checkoutConfig.payment.swppayment.istoken19 === "true"){
                                                msg.Message = "There was an error capturing the card data, please try again";
                                            }
                                            //custommsg.responseText = JSON.stringify({message: msg.Message});
                                            if(msg.Message == "Error in Form"){
                                                $("#NewCenposPlugin iframe").height(530);
                                            }else  $("#NewCenposPlugin iframe").height(510);
                                            self.showalert("Error", msg.Message , function(){});
                                            //errorProcessor.process(custommsg);
                                        }
                                });
                                
                                $(".methods-shipping .continue").hide();
                                $("#ChangeShippingContinue").show();
                                if(!self.webpayinit){
                                        $(".methods-shipping .continue").parent().append("<button type='button' id='ChangeShippingContinue' />");
                                        $("#ChangeShippingContinue").attr("class", $(".methods-shipping .continue").attr("class")).removeClass("continue");
                                        $("#ChangeShippingContinue").html($(".methods-shipping .continue").html());
                                        $("#ChangeShippingContinue").click(function(){
                                            $(".methods-shipping .continue").show();
                                            $(".methods-shipping .continue").trigger("click");
                                            $("#ChangeShippingContinue").hide();
                                            if(self.webpayinit) self.createWebpay();
                                        });
                                        $("#SubmitWebpay").on('click', function () {
                                            $("#NewCenposPlugin > div").submitAction();
                                        });
                                        $("<style type='text/css'> .dpnoneimpo{ display:none !important} </style>").appendTo("head");
                                }
                                self.webpayinit = true;

                                $("#cenposPayIFrameId").attr("style", "border: none !important;margin-top: 0px;");
                            } else {
                                var custommsg = {};
                              
                               // custommsg.responseText = JSON.stringify({message: msg2.Message});
                                
                                self.showalert("Error", msg2.Message , function(){});
                               //errorProcessor.process(custommsg);
                                $(".payment-method-result-webpay").append("<a id='ReloadPayment' style='display:block; cursor: pointer'>Reload Payment/a>");
                                $("#ReloadPayment").click(function(){self.createWebpay();});
                            }
                        }
                    });
                }catch(error)
                {
                    $("#loadersavecard").remove();
                    fullScreenLoader.stopLoader();
                    self.showalert("Error", JSON.stringify(error) + ". Please try again or another credit card");
                   // errorProcessor.process(custommsg);
                }
            },

            getCode: function () {
                return 'swppayment';
            },
            getData: function () {
                var additional_data = {};
                $("#FormWebpay").children().each(function () {
                    additional_data[$(this).attr("name")] = $(this).val();
                });

                return {
                    'method': this.item.method,
                    'additional_data': additional_data
                };
            },
            getEmail: function () {
                return quote.guestEmail;
            },
            showalert:function(title,content, action){
                alert({
                    title: $.mage.__(title),
                    content: $.mage.__(content),
                    actions: {
                        always: action
                    }
                });
            },
            afterPlaceOrder: function (data, event) {
                
            },
            placeOrder: function (data, event) {
                var self = this;
                var msgtemp = {};
                var eventtemp = {};
                if (event) {
                    event.preventDefault();
                }
                $("#CardinalResponse").off("change");
                $("#CardinalResponse").on('change', function () {
                    var Value = $(this).val();
                    if (Value !== "") {
                        var resposems = JSON.parse(Value);
                        $("#Form3dSecure").hide();
                        if (resposems.Result !== 0) {
                            self.isPlaceOrderActionAllowed(true);
                           // eventtemp.responseText = JSON.stringify({message: msgtemp.Message});
                            self.showalert("Error", msgtemp.Message);
                           // errorProcessor.process(eventtemp);
                            fullScreenLoader.stopLoader();
                        } else {
                            $.ajax({
                                type: "POST",
                                url: window.checkoutConfig.payment.swppayment.url3d,
                                data: resposems,
                                beforeSend: function () {
                                    self.isPlaceOrderActionAllowed(false);
                                    fullScreenLoader.startLoader();
                                },
                                success: function (msg) {
                                    msg = $.parseJSON(msg);
                                    if (msg.Result === "0") {
                                        $("#SubmitWebpaySend").trigger("click");
                                    } else {
                                            self.isPlaceOrderActionAllowed(true);
                                            fullScreenLoader.stopLoader();
                            
                                            self.showalert("Error", msg.Message, function(){self.createWebpay();});

                                           // errorProcessor.process(custommsg);
                                            //self.createWebpay();
                                    }
                                }
                            });
                        }
                    }
                });
                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    this.getPlaceOrderDeferredObject()
                        .fail(
                            function (msg, data, data2) {
                                try{
                                    if(msg.responseJSON){
                                        var result = JSON.parse(msg.responseJSON.message);
                                        $(".payment-method-content .messages").removeClass("dpnoneimpo");
                                        if (result.Result === 21) {
                                            $(".payment-method-content .messages").addClass("dpnoneimpo");
                                            fullScreenLoader.stopLoader();
                                            result.View3D = result.View3D.replace("function(messageEvent){", "function(messageEvent){ document.getElementById('CardinalResponse').value = messageEvent.data; document.getElementById('CardinalResponse').dispatchEvent(new Event('change')); ");
                                            result.View3D = msg.View3D.replace("window['returnCardinalMag'](messageEvent.data)", "");
                                            result.View3D = result.View3D.replace("framecenpos'  width='100%'", "framecenpos' width='100%' height='400'");
                                            $("#Form3dSecure").show();
                                            $("#Form3dSecure").html("<div>" + result.View3D + "</div>");
                                        }else {
                                            self.createWebpay();
                                           // $(".payment-method-content .messages").addClass("dpnoneimpo");
                                        }
                                    }
                                }catch(e){
                                    self.createWebpay();
                                }
                                self.isPlaceOrderActionAllowed(true);
                            }
                        ).done(
                            function (msg, message, event) {
                                try{
                                    msgtemp = msg;
                                    eventtemp = event;
                                    if (Number.isInteger(Number.parseInt(msg))) {
                                        msg = { Result: 0, Message: "Approval" }
                                    } else{
                                        if(msg.indexOf("{") == 0){
                                            var msgsplit = msg.split("}");
                                            var msgcompu = "";
                                            for(var n in msgsplit){
                                                if(msgsplit[n].indexOf("View3D") > 0 || n==0 ){
                                                    msgcompu = msgsplit[n] + "}";
                                                    break;
                                                }
                                            }
                                            try{
                                                msg = $.parseJSON(msgcompu);
                                            }catch(error)
                                            {
                                                msg =  msg.slice(0,msg.indexOf("['returnCardinalMag'](messageEvent.data)}});")+"['returnCardinalMag'](messageEvent.data)}}); </script>'a}".length);
                                                msg = $.parseJSON(msg);
                                            }
                                        }else{
                                            var  resp = {};
                                            resp.Message = msg;
                                            resp.Result = -1;
                                            msg = resp;
                                        }
                                    } 
                                    if (msg.Result === 0) {
                                        self.afterPlaceOrder();
                                        if (self.redirectAfterPlaceOrder) {
                                            redirectOnSuccessAction.execute();
                                        }
                                    } else {
                                        self.isPlaceOrderActionAllowed(true);
                                        msg.message = msg.Message;
                                        event.responseText = JSON.stringify(msg);
                                        
                                         self.showalert("Error", event.responseText + ". Please try again or another credit card", function(){self.createWebpay();});
                                      //  errorProcessor.process(event);
                                        fullScreenLoader.stopLoader();
                                       // self.createWebpay();
                                    }
                                }catch(err){
                                    self.isPlaceOrderActionAllowed(true);
                                    fullScreenLoader.stopLoader();
                                    event.responseText = JSON.stringify(err);
                                    self.showalert("Error", event.responseText + ". Please try again or another credit card", function(){self.createWebpay();});
                                    //self.createWebpay();
                                }
                            }
                        );

                    return true;
                }

                return false;
            },

            reloadPayment: function() {
                var self = this;
                fullScreenLoader.startLoader();
               return true;
            } 
        });
    }
);