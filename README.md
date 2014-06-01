# Currency Conversion Service

## Module for ProcessWire

This module is designed for performing currency conversions among ~165
different currencies. It uses OpenExchangeRates.org (or compatible) 
for data so that currency exchange rates are always up-to-date. 

THe module provides functions that you can use to convert from one 
currency to another via the API. This is especially handy for generating 
rate tables in multiple currencies or giving users of your site the option 
to see prices in their currency. 


### How to install

1. Copy the files here into /site/modules/ServiceCurrencyConversion/
2. In your ProcessWire admin, check for new modules and click install for this module.
3. Obtain an OpenExchangeRates.org API key. Because this module caches the exchange rate data
   for a period that you specify, you likely will only need the free account unless you 
   need up-to-the-minute exchange rate data. 
    - [Free account](https://openexchangerates.org/signup/free)
    - [Commercial account](https://openexchangerates.org/signup)
4. Paste your key into the module configuration screen where prompted to do so.
   After saving, you should see a table indicating current exchange rate data. 


### How to use

Usage is best demonstrated by example. Here is a basic example that 
demonstrates conversion of a rate from USD (US Dollars) to EUR (Euros). The
context for these examples is from one of your site template files. 

``````
$cc = $modules->get('ServiceCurrencyConversion'); 
$dollars = 100; // amount of currency we want to convert
$euros = $cc->convert('USD', 'EUR', $dollars); 
echo "<p>$dollars US Dollars equals $euros Euros</p>"; 
``````

USD and EUR can be any currency codes known by OpenExchangeRates.org, meaning
you should be able to convert between any two currencies that you want to. 

For a live example of a currency conversion tool, try out the included convert.php
file included with this module. Copy it to your /site/templates/, add it as a new
template, and create a page with it. 


## API

The following methods are provided by the Service Exchange Rates module. 
All of the example calls below assume you have a copy of the ServiceCurrencyConversion
module in the variable $ex, obtained by a call like this: 

`````````
$cc = $modules->get('ServiceCurrencyConversion'); 
`````````

#### convert($fromCurrency, $toCurrency, $amount)

Convert an amount from one currency to another and return the converted amount. 
For $fromCurrency and $toCurrency, specify the 3-digit currency code. 

`````````
// how many dollars are there in 100 euros?
echo $cc->convert('EUR', 'USD', 100); // outputs 136.335 (when I tested)
`````````


#### getSymbol($currency)

Return the currency symbol used by the given currency code. 

`````````
echo $cc->getSymbol('USD'); // outputs "$"
`````````

#### getName($currency)

Return the currency name (in English) for the given currency code. 

`````````
echo $cc->getName('EUR'); // outputs "Euro"
`````````

#### lastUpdated()

Returns the time (UNIX timestamp) that the exchange rate data was last updated. 

````````
$time = $cc->lastUpdated();
echo "<p>Last updated: " . date('Y-m-d H:i:s', $time) . "</p>";
````````


#### updateNow()

Force the exchange rates to update now. 

```````
$success = $cc->updateNow();
if($success) echo "Rates were updated";
```````


#### getRatesTable($useCache = true)

Return an array with all exchange rate data indexed by currency code
with each item containing the currency name, symbol and USD exchange rate

`````````
$data = $cc->getRatesTable($useCache = true); 
print_r($data); 
````````
The above would output: 
````````
array(
  'USD' => array(
  'code' => 'USD',
  'name' => 'US Dollars',
  'symbol' => '$',
  'x' => 1 // all exchange rates USD
  ), 
  'EUR' => array(
  // ...	
  ), 
  // ... and so on for all currencies
); 
````````		

The getRatesTable method accepts a $useCache method, which is true by default.
If set to false, it will retrieve the data from OpenExchangeRates.org immediately
without considering a previously retrieved local copy of rates (cache). 


#### getConvertedRatesTable($fromCode = 'USD', $amount = 1)

Get exchange rates from one currency to all others. Returns an array of all
data indexed by currency code with each item containing the currency name, 
symbol, exchange rate (from USD), and converted amount. 

````````
// how much is 5 Euros in all other currencies?
$data = $cc->getConvertedRatesTable('EUR', 5); 
print_r($data); 
````````
The above would output: 
````````
array(
  'CAD' => array(
    'code' => 'CAD',
    'name' => 'Canadian Dollar',
    'symbol' => '$',
    'x' => 1.08459 // USD exchange rate
    'amount' => 7.39332209033
  ),
  // ... and so on for all currencies
)
````````		

	