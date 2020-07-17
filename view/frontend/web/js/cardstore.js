define([
    "jquery",
    "porthole", 
    'Magento_Checkout/js/model/full-screen-loader',
    'Magento_Checkout/js/model/error-processor',
    "viewprocess"
], function($,porthole, fullScreenLoader, errorProcessor, vpr) {
    //<![CDATA[
        $.widget('cenpos.simplewebpay', {
            _create: function() {
                getsession(this.options.url, this.options.urlsession);
            },
          
        });

        var count = 0;
        function getsession(urlprocess, urlsession){
            var DataConfig = '{"Theme":"collapse","View":"1","EnabledView":["1","2","3"],"ColorBg":"#00aabb","ColorCtrl":"#00aabb","ColorIcon":"#00aabb","EffectView":"bounce","Column":[{"Name":"Expiration Date","Code":"CardExpirationDate"},{"Name":"Card Number","Code":"CardNumber"},{"Name":"Card Type","Code":"CardType","Style":"Image"}],"LabelForm":false,"IconInput":true,"HeadernoRow":true,"HeaderBottomList":true,"HeaderTopList":true,"Pagination":false,"SearchList":false,"Ordering":true,"Operation":["get","add","edit","delete"],"IsCVV":false,"IsTooltipCVV":false,"ActionSelected":"radio","OperationType":"each","OperationStyle":"buttontext","OperationTooltip":true,"IsToken19":false,"CryptoToken":false,"OperationView":"popup","Callbacks":[{"Code":"get","Value":"none"},{"Code":"add","Value":"popup"},{"Code":"edit","Value":"popup"},{"Code":"delete","Value":"popup"}],"Buttons":[{"Code":"get","Action":"tokens","Label":""},{"Code":"add","Action":"add","Label":""},{"Code":"add","Action":"submit","Label":""},{"Code":"add","Action":"cancel","Label":""},{"Code":"edit","Action":"edit","Label":""},{"Code":"edit","Action":"submit","Label":""},{"Code":"edit","Action":"cancel","Label":""},{"Code":"delete","Action":"delete","Label":""},{"Code":"delete","Action":"submit","Label":""},{"Code":"delete","Action":"cancel","Label":""}],"RecaptchaEnable":false,"DoubleClick":false,"OneClick":false,"IsEmail":false,"IsZipcode":true,"IsAddress":true,"IsCustomer":false,"FirstAdd":false,"EmptyTokenAdd":false}';
            DataConfig = JSON.parse(DataConfig);
            $.ajax({
                type: "POST",
                url: urlsession,  
                showLoader: true, // enable loader
                context: "#contentarea",
                beforeSend: function () {
                },
                success: function (msg) {
                    msg = $.parseJSON(msg);
                    if (msg.Result === "0") {
                        $("#viewprocess").createViewProcess(
                        {
                            url: urlprocess,
                            verifyingpost: msg.Data,
                            type: "Cards/Manage/Customer",
                            data: DataConfig,      
                            width:  "100%",
                            Open:function(){

                            },
                            height:"600"
                        });
                    } else {
                        msg.message = msg.Message;
                        event.responseText = JSON.stringify(msg);
                        errorProcessor.process(event);
                    }
                },error:function(er,aa,aas){
                    count++
                    if(count == 3){
                        event.responseText = JSON.stringify("Error, Please Reload the page");
                        errorProcessor.process(event);
                        alert("Error, Please Reload the page");
                    }else getsession(urlprocess,urlsession)
                   
                }
            });
        }

        return $.cenpos.simplewebpay;
    //]]>
});