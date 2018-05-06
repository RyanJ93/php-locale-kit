<?php
require dirname(__FILE__) . '/../php-locale-kit.php';
//require dirname(__FILE__) . '/../php-tiny-cacher.php';
	
use PHPTinyCacher\PHPTinyCacher;
use PHPLocaleKit\Package;

$path = 'demo.db';

if ( in_array('--cache', $argv) === true ){
	$cache = new PHPTinyCacher();
	//Setting up cache.
	echo 'Running the test script with caching enabled...' . PHP_EOL;
	$cache->setStrategy(PHPTinyCacher::STRATEGY_REDIS)->setNamespace('demo')->setVerbose(true)->connectToRedis('127.0.0.1', 6379, 0);
}
$start = microtime(true);
$package = new Package();
$package->setVerbose(true);
if ( isset($cache) === true ){
	$package->setCache(true)->setCacheHandler($cache);
}
//Setting the path to the package.
echo 'Setting package path...' . PHP_EOL;
$package->setPath($path);
echo 'Connected to package, trying to set "en-US" as locale...' . PHP_EOL;
//Setting the locale code...
$package->setLocale('en-US', false);
//Checking if the locale that has been set is a fallback or is the same that has been specified.
if ( $package->isFallback() === true ){
	echo '"en-US" appears to be not supported by the package, falling back to another English variant if available...' . PHP_EOL;
	//Getting the fallback locale selected by the library.
	echo 'Locale has been set to "' . $package->getLocale() . '", now getting labels with IDs 1, 2 and 3...' . PHP_EOL;
}else{
	echo 'Locale has been set, now getting labels with IDs 1, 2 and 3...' . PHP_EOL;
}
//Getting the labels by their IDs.
$lables = $package->getLabels(array(1, 2, 3));
echo 'Labels fetched, here you are the label with ID 2: ' . $lables[2] . PHP_EOL;
echo 'Fetching all languages supported by this package...' . PHP_EOL;
//Getting the list of all the languages supproted by the package.
$locales = $package->getSupportedLocales();
$locales = array_map(function($element){
	return $element['locale'];
}, $locales);
echo 'This package supports these locales: ' . implode(', ', $locales) . '.' . PHP_EOL;
echo 'Checking if Italian is supported by the package...' . PHP_EOL;
//Checking if a specific locale is supported by the package.
echo 'Is Italian supported? ' . ( $package->isLocaleSupported('it') === true ? 'Yes' : 'No' ) . '.' . PHP_EOL;
if ( isset($cache) === true ){
	echo 'Invalidating the cache...' . PHP_EOL;
	//Removing all cached data.
	$cache->invalidate();
}
echo 'Demo completed in ' . ( microtime(true) - $start ) . ' seconds.' . PHP_EOL;