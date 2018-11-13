define([
    "jquery",
    "porthole", 
    "viewprocess"
], function($) {
    //<![CDATA[
        $.widget('cenpos.simplewebpay', {
            _create: function() {
                var DataConfig = '{"Theme":"collapse","View":"1","EnabledView":["1","2","3"],"ColorBg":"#00aabb","ColorCtrl":"#00aabb","ColorIcon":"#00aabb","EffectView":"bounce","Column":[{"Name":"Expiration Date","Code":"CardExpirationDate"},{"Name":"Card Number","Code":"CardNumber"},{"Name":"Card Type","Code":"CardType","Style":"Image"}],"LabelForm":false,"IconInput":true,"HeadernoRow":true,"HeaderBottomList":true,"HeaderTopList":true,"Pagination":false,"SearchList":false,"Ordering":true,"Operation":["get","add","edit","delete"],"IsCVV":false,"IsTooltipCVV":false,"ActionSelected":"radio","OperationType":"each","OperationStyle":"buttontext","OperationTooltip":true,"IsToken19":false,"CryptoToken":false,"OperationView":"popup","Callbacks":[{"Code":"get","Value":"none"},{"Code":"add","Value":"popup"},{"Code":"edit","Value":"popup"},{"Code":"delete","Value":"popup"}],"Buttons":[{"Code":"get","Action":"tokens","Label":""},{"Code":"add","Action":"add","Label":""},{"Code":"add","Action":"submit","Label":""},{"Code":"add","Action":"cancel","Label":""},{"Code":"edit","Action":"edit","Label":""},{"Code":"edit","Action":"submit","Label":""},{"Code":"edit","Action":"cancel","Label":""},{"Code":"delete","Action":"delete","Label":""},{"Code":"delete","Action":"submit","Label":""},{"Code":"delete","Action":"cancel","Label":""}],"RecaptchaEnable":false,"DoubleClick":false,"OneClick":false,"IsEmail":false,"IsZipcode":false,"IsAddress":false,"IsCustomer":false,"FirstAdd":false,"EmptyTokenAdd":false}';
                
                DataConfig = JSON.parse(DataConfig);
                $("#viewprocess").createViewProcess(
                {
                    url: this.options.url,
                    verifyingpost: this.options.data,
                    type: "Cards/Manage/Customer",
                    data: DataConfig,      
                    width:  "100%",
                    height:"600"
                });
            }
    
        });
        return $.cenpos.simplewebpay;
    //]]>
});