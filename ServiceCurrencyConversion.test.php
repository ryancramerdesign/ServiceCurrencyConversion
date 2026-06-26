<?php namespace ProcessWire;

/**
 * Tests for ServiceCurrencyConversion
 *
 */
class WireTest_ServiceCurrencyConversion extends WireTest {

	/**
	 * @var ServiceCurrencyConversion
	 *
	 */
	protected $module;

	/**
	 * @var string
	 *
	 */
	protected $cacheFile = '';

	/**
	 * @var string
	 *
	 */
	protected $cacheFilePrev = '';

	/**
	 * @var array
	 *
	 */
	protected $savedFiles = array();

	public function init() {
		$this->module = $this->wire()->modules->get('ServiceCurrencyConversion');
		if(!$this->module) throw new WireTestException('ServiceCurrencyConversion module is not installed');

		$this->cacheFile = $this->callProtected('cacheFilename');
		$this->cacheFilePrev = $this->cacheFile . '.prev';
		$this->saveFileState($this->cacheFile);
		$this->saveFileState($this->cacheFilePrev);
	}

	public function execute() {
		$this->testJsonDecodeRates();
		$this->testCacheFallbackRestoresPreviousRates();
		$this->testRatesTableAndConversions();
		$this->testInvalidCurrenciesReturnFailureValues();
	}

	public function finish() {
		foreach($this->savedFiles as $file => $info) {
			if($info['exists']) {
				$this->wire()->files->filePutContents($file, $info['contents']);
				if($info['mtime']) touch($file, $info['mtime']);
			} else if(is_file($file)) {
				$this->wire()->files->unlink($file);
			}
		}
	}

	protected function testJsonDecodeRates() {
		$this->check('jsonDecodeRates() reports empty response', 'Empty response', $this->decodeError(''));
		$this->check('jsonDecodeRates() reports invalid JSON', 'Syntax error', $this->decodeError('{bad json'));
		$this->check('jsonDecodeRates() reports empty data', 'Empty data', $this->decodeError('[]'));

		$errorJSON = json_encode(array(
			'error' => true,
			'status' => 401,
			'message' => 'invalid_app_id',
			'description' => 'Invalid App ID provided.',
		));
		$this->check('jsonDecodeRates() preserves API message and description', 'invalid_app_id Invalid App ID provided.', $this->decodeError($errorJSON));

		$this->check('jsonDecodeRates() rejects missing rates', 'Missing or invalid rates', $this->decodeError(json_encode(array('base' => 'USD'))));
		$this->check('jsonDecodeRates() rejects invalid USD rate', 'Missing or invalid USD rate', $this->decodeError(json_encode(array('rates' => array('USD' => 'oops')))));

		$data = $this->callProtected('jsonDecodeRates', array($this->ratesJSON()));
		$this->check('jsonDecodeRates() accepts valid rates', true, empty($data['error']) && isset($data['rates']['EUR']));
	}

	protected function testCacheFallbackRestoresPreviousRates() {
		$files = $this->wire()->files;

		$files->filePutContents($this->cacheFile, json_encode(array('rates' => array('USD' => 'oops'))));
		$files->filePutContents($this->cacheFilePrev, $this->ratesJSON(array('EUR' => 0.8, 'UYU' => 40)));

		$data = $this->callProtected('getLatestData');
		$this->check('getLatestData() falls back to previous valid rates', 0.8, (float) $data['rates']['EUR']);

		$restored = $this->callProtected('jsonDecodeRates', array($files->fileGetContents($this->cacheFile)));
		$this->check('getLatestData() restores previous rates to active cache', 40.0, (float) $restored['rates']['UYU']);
	}

	protected function testRatesTableAndConversions() {
		$table = $this->module->getRatesTable();

		$this->check('getRatesTable() includes USD from fixture', 1.0, $table['USD']['x']);
		$this->check('getRatesTable() includes EUR from fixture', 0.8, $table['EUR']['x']);
		$this->check('getRatesTable() includes UYU from fixture', 40.0, $table['UYU']['x']);

		$plain = $this->module->convertAdvanced('EUR', 'UYU', 100, 0, 6);
		$this->check('convertAdvanced() converts non-USD source to target', 5000.0, $plain['amount']);

		$marked = $this->module->convertAdvanced('EUR', 'UYU', 100, 0.023, 6);
		$this->check('convertAdvanced() applies markup once to non-USD source conversion', 5115.0, $marked['amount']);
		$this->check('convertAdvanced() preserves unmarked amount_actual', 5000.0, $marked['amount_actual']);

		$usd = $this->module->convertAdvanced('EUR', 'USD', 100, 0.023, 6);
		$this->check('convertAdvanced() marks USD target amount', 127.875, $usd['amount']);
		$this->check('convertAdvanced() preserves unmarked USD amount_actual', 125.0, $usd['amount_actual']);

		$this->check('convert() accepts lowercase currency codes', 80.0, $this->module->convert('usd', 'eur', 100));
	}

	protected function testInvalidCurrenciesReturnFailureValues() {
		$this->check('convert() returns 0 for invalid source currency', 0, $this->module->convert('XXX', 'USD', 100));
		$this->check('convert() returns 0 for invalid target currency', 0, $this->module->convert('USD', 'XXX', 100));

		$rate = $this->module->convertAdvanced('XXX', 'USD', 100);
		$this->check('convertAdvanced() returns FAIL name for invalid source currency', 'FAIL', $rate['name']);
		$this->check('convertAdvanced() returns error message for invalid source currency', 'Cannot find from currency', $rate['error'], '*=');
	}

	protected function decodeError($json) {
		$data = $this->callProtected('jsonDecodeRates', array($json));
		return isset($data['error']) ? $data['error'] : '';
	}

	protected function ratesJSON(array $rates = array()) {
		$rates = array_merge(array(
			'USD' => 1,
			'EUR' => 0.8,
			'UYU' => 40,
		), $rates);

		return json_encode(array(
			'disclaimer' => 'WireTests fixture',
			'license' => 'WireTests fixture',
			'timestamp' => 1234567890,
			'base' => 'USD',
			'rates' => $rates,
		), JSON_PRETTY_PRINT);
	}

	protected function callProtected($method, array $args = array()) {
		$reflection = new \ReflectionMethod($this->module, $method);
		$reflection->setAccessible(true);
		return $reflection->invokeArgs($this->module, $args);
	}

	protected function saveFileState($file) {
		$this->savedFiles[$file] = array(
			'exists' => is_file($file),
			'contents' => is_file($file) ? file_get_contents($file) : '',
			'mtime' => is_file($file) ? filemtime($file) : 0,
		);
	}
}
