<?php
require dirname(__FILE__) . '/../php-locale-kit.php';
//require dirname(__FILE__) . '/../php-tiny-cacher.php';
	
use PHPTinyCacher\PHPTinyCacher;
use PHPLocaleKit\Translator;

$token = 'YOUR API KEY HERE';
$translate = array('E la volpe con il suo balzo superò il quieto fido.', 'Ciao mondo!!');
$detect = array('E la volpe con il suo balzo superò il quieto fido.', 'Hello world!');

if ( in_array('--cache', $argv) === true ){
	$cache = new PHPTinyCacher();
	//Setting up cache.
	echo 'Running the test script with caching enabled...' . PHP_EOL;
	$cache->setStrategy(PHPTinyCacher::STRATEGY_REDIS)->setNamespace('demo')->setVerbose(true)->connectToRedis('127.0.0.1', 6379, 0);
}
$start = microtime(true);
$translator = new Translator();
$translator->setVerbose(true);
//Let's use Yandex.Translate as service provider using its free plan.
$translator->setupYandex($token, Translator::HTML);
if ( isset($cache) === true ){
	$translator->setCache(true)->setCacheHandler($cache);
}
//Translate the texts from Italian to English.
echo 'Translating some texts...' . PHP_EOL;
$elements = $translator->translateText($translate, 'en', 'it');
echo 'The first text translated: ' . array_values($elements)[0] . PHP_EOL;
echo 'Detecting the language of some texts...' . PHP_EOL;
//Detect the language of some texts.
$elements = $translator->detectLanguage($detect);
echo 'Language detection for the text "E la volpe con il suo balzo superò il quieto fido": ' . $elements[$detect[0]] . PHP_EOL;
echo 'Language detection for the text "Hello world!": ' . $elements[$detect[1]] . PHP_EOL;
echo 'Fetching a list of all supported languages from the provider...' . PHP_EOL;
//Fetching a list of all supported languages from the provider.
$elements = $translator->getSupportedLanguages('en');
echo 'This provider supports these languages: ' . implode(', ', $elements) . PHP_EOL;
if ( isset($cache) === true ){
	echo 'Invalidating the cache...' . PHP_EOL;
	//Removing all cached data.
	$cache->invalidate();
}
echo 'Demo completed in ' . ( microtime(true) - $start ) . ' seconds.' . PHP_EOL;