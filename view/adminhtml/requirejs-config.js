/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

var config = {
    paths: {
        'simplewebpay': 'https://www.cenpos.com/Plugins/jquery.simplewebpay_aug2017.min',
        'viewprocess': 'https://development.cenpos.com/jquery.viewprocess',
        'porthole': 'https://staging.cenpos.com/Plugins/porthole.min'
    },
    shim: {
        'simplewebpay': {
            deps: ['jquery']
        },
        'viewprocess': {
            deps: ['jquery']
        }
    }
};
