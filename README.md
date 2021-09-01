[![MIT Licensed](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/daalder/exact/run-tests?label=tests)
[![Quality Score](https://img.shields.io/scrutinizer/g/Daalder/exact.svg?style=flat-square)](https://scrutinizer-ci.com/g/Daalder/exact)

# daalder/exact
Exact integration for Daalder

##Setup instructions
####Add the `daalder/exact` repository to your `composer.json`

```
"repositories": [
    {
        "type": "git",
        "url": "git@github.com:daalder/exact.git"
    }
],
```

####Install the package

``composer require daalder/exact``

####Publish and run the migrations

```
php artisan vendor:publish --tag=daalder-exact-migrations
php artisan migrate
```

####Configure the VAT rates

A new column has been added to the Daalder `vat_rates` table (`exact_code`) that needs to be filled for each VAT rate. The package uses this field to match Daalder and Exact VAT rates. Please refer to the Exact documentation for fetching these VAT codes. 

