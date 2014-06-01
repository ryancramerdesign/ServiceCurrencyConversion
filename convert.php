<?php 

/**
 * Currency Conversion Tool
 * 
 * This is fully working example of a currency conversion tool built with the 
 * Service Currency Conversion module. It is provided for demonstration purposes
 * only. To install, copy this file to your /site/templates/ directory, and go
 * to Setup > Templates > New, in your PW admin. Add the template and create a
 * new page using the template. View the page to test the currency conversion.
 *
 */ 

if(!defined("PROCESSWIRE")) throw new WireException("This file requires ProcessWire"); 

?><!DOCTYPE html>
<html>
<head>
	<title>Currency Conversion</title>
</head>
<body>

	<h1>Currency Conversion</h1>
	<h3>An example of the Service Currency Conversion module for ProcessWire</h3>

	<form method='post' action='<?php echo $page->url; ?>'>

	<?php
		
	$cc = $modules->get('ServiceCurrencyConversion'); 

	// build select options for form
	$data = $cc->getRatesTable();	
	$optionsFrom = '';
	foreach($data as $code => $currency) {
		$optionsFrom .= "<option value='$code'>$code: $currency[name]</option>";
	}

	$optionsTo = $optionsFrom; 
	$amountFrom = '';

	if($input->post->submit) {
		// a currency conversion was requested

		$currencyFrom = $sanitizer->name($input->post->currency_from); 
		$currencyTo = $sanitizer->name($input->post->currency_to); 
		$amountFrom = (float) $input->post->amount_from; 

		if($currencyFrom && $currencyTo && $amountFrom > 0) {
			// perform the conversion
			$amountTo = $cc->convert($currencyFrom, $currencyTo, $amountFrom); 

			// we will round to 2 decimals for presentation purposes
			$amountFrom = round($amountFrom, 2); 
			$amountTo = round($amountTo, 2); 

			// get other info about the currencies for presentation purposes
			$nameFrom = $cc->getName($currencyFrom); 
			$nameTo = $cc->getName($currencyTo); 
			$symbolFrom = $cc->getSymbol($currencyFrom); 
			$symbolTo = $cc->getSymbol($currencyTo); 

			echo "<h2>$symbolFrom $amountFrom $nameFrom = $symbolTo $amountTo $nameTo</h2>";
			
		} else {
			echo "<h2>Missing required fields</h2>";
		}

		// make the relevant items already selected if they submitted the form
		$optionsFrom = str_replace("<option value='$currencyFrom'>", "<option selected value='$currencyFrom'>", $optionsFrom); 
		$optionsTo = str_replace("<option value='$currencyTo'>", "<option selected value='$currencyTo'>", $optionsTo); 
	}

	?>

	<p>
	<select name='currency_from'>
		<option>Currency From</option>
		<?php echo $optionsFrom; ?>
	</select>
	</p>

	<p>
	<select name='currency_to'>
		<option>Currency To</option>
		<?php echo $optionsTo; ?>
	</select>
	</p>

	<p><input type='text' name='amount_from' placeholder='Amount' value='<?php echo $amountFrom; ?>' /></p>
	<p><input type='submit' name='submit' value='Convert' /></p>

	</form>

	<p>Exchange rate data last updated <?php echo date('F j, Y G:i a', $cc->lastUpdated()); ?></p>
	<p>Exchange rates provided by <a href='http://openexchangerates.org'>OpenExchangeRates.org</a></p>


</body>
</html>
