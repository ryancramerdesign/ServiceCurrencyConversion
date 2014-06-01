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

if(!defined("PROCESSWIRE")) die("This file requires ProcessWire"); 

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
	$names = $cc->getNames(); // names of currencies, indexed by 3-digit codes
	$amount = '';
	$options = '';

	foreach($names as $code => $name) {
		$options .= "<option value='$code'>$code: $name</option>";
	}

	$optionsFrom = $options; 
	$optionsTo = $options; 

	if($input->post->submit) {

		// a currency conversion was requested, so sanitize submitted data
		$from = array_key_exists($input->post->from, $names) ? $input->post->from : '';
		$to = array_key_exists($input->post->to, $names) ? $input->post->to : '';
		$amount = (float) $input->post->amount; 

		if($from && $to && $amount > 0) {

			// perform the conversion
			$converted = $cc->convert($from, $to, $amount); 

			// we will round to 2 decimals for presentation purposes
			$converted = round($converted, 2); 

			// get other info about the currencies for presentation purposes
			$nameFrom = $names[$from]; 
			$nameTo = $names[$to]; 
			$symbolFrom = $cc->getSymbol($from); 
			$symbolTo = $cc->getSymbol($to); 

			echo "<h2>$symbolFrom $amount $nameFrom = $symbolTo $converted $nameTo</h2>";
			
		} else {
			echo "<h2>Missing required fields</h2>";
		}

		// make the relevant items already selected if they submitted the form
		$optionsFrom = str_replace("<option value='$from'>", "<option selected value='$from'>", $optionsFrom); 
		$optionsTo = str_replace("<option value='$to'>", "<option selected value='$to'>", $optionsTo); 
	}

	?>

	<p>
	<select name='from'>
		<option>Currency From</option>
		<?php echo $optionsFrom; ?>
	</select>
	</p>

	<p>
	<select name='to'>
		<option>Currency To</option>
		<?php echo $optionsTo; ?>
	</select>
	</p>

	<p><input type='text' name='amount' placeholder='Amount' value='<?php echo $amount; ?>' /></p>
	<p><input type='submit' name='submit' value='Convert' /></p>

	</form>

	<p>Exchange rate data last updated <?php echo date('F j, Y G:i a', $cc->lastUpdated()); ?></p>
	<p>Exchange rates provided by <a href='http://openexchangerates.org'>OpenExchangeRates.org</a></p>


</body>
</html>
