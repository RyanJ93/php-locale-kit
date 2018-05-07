# PHP Locale Kit

PHP Locale Kit is a simple library that allows to manage language packages based on SQLite3 database. It provide also utilities that allow to translate texts as well as detect their language supporting Google and Yandex as service provider. The package is shipped with a CLI utility that allows to create and translate language packages, for more information and documentation check the file called "cli.md".

## Installation

Before installing the package, make sure that the `sqlite3` extension is installed, usually this extension is shipped with your PHP installation. To install this package through Composer just run this command:

````bash
composer require ryanj93/php-locale-kit
````

## Usage: Package

The class "Package" allows to fetch the labels from the language package, before using it you need, of course, to set up the path to the package and the locale to use, the locale must be supported by the package, you can set a locale and allows to the library to switch to a fallback locale based on the language of the given locale code, for example, if you set "en-US" as locale but the package doesn't support it, the library will look for any locale matching with "en", unless the strict mode is enable, here you are an example:

````php
$package = new Package();
//path, locale, strict mode
//$locale is a string containing the locale code selected, if different by the given code, it means that a fallback locale has been picked.
$locale = $package->setPackage('path/to/the/package.db', 'en-US', false);
````

You can get a list of all the supported locales by using the following method:

````php
//Get all locales.
$locales = $package->getSupportedLocales();
//Check for a specific locale support.
//locale, strict mode
$value = $package->isLocaleSupported('en-US', false);
````

Now you are able to get the labels, you can use the following method passing an array containing the labels' IDs, the IDs can be represented as numbers or strings, according with the package, the labels will be returned as associative array having as key the label ID and as value its text, if a label were not found, it will be omitted, here's the example:

````php
$labels = $package->getLabels(array(1, 2, 3));
````

Of course you can fetch all the labels from the package using the following method:

````php
$labels = $package->getAllLabels();
````

## Usage: Translator

The class "Translator" allows to translate and detect the language of texts, as of now, it support only Google and Yandex as service provider, in order to use it, you need to get an API key from the provider that you are going to use, by default, Yandex is used because it offers a free plan that allows to translate up to 10000000 chars per month, you can get a free API key from Yandex [here](https://translate.yandex.com/developers), here you are the setup example:

````php
$translator = new Translator();
//token, text format
$translator->setupYandex('YOUR API TOKEN HERE', Translator::HTML);
````

Now you can translate one or more texts using this method:

````php
//text, target language, original language
$texts = $translator->translateText(array('Ciao mondo!'), 'en', 'it');
````

Note that you can omit the original language, in this case it will be automatically detected by the provider. The translated texts will be returned as associative array having as key the original text and as value the translated one. In a similar way you can detect the language of one or more texts, here you are an example:

````php
//text, target language, original language
$detections = $translator->detectLanguage(array('Ciao mondo!'));
````

It will return an associative array having as key the original text and as value the code of the language detected. If you need to get a list of all the languages supported by the service provider you can use the following method:

````php
$languages = $translator->getSupportedLanguages('en');
````

Both the classes support data caching, you can set up cache using these methods:

````php
//Setup cached for the package.
$package->setCache(true)->setCacheHandler($cache);
//Setup cached for the translator.
$translator->setCache(true)->setCacheHandler($cache);
````

Data caching is provided by the library "php-tiny-cacher", as you can see in the examples, the variable "$cache" is an instance of the class "PHPTinyCacher" that allows to store data using different options such as Redis, Memcached and file. You can find more information about it on its [repository on GitHub](https://github.com/RyanJ93/php-tiny-cacher).

If you like this project and think that is useful don't be scared and feel free to contribute reporting bugs, issues or suggestions or if you feel generous, you can send a donation [here](https://www.enricosola.com/about#donations).

Are you looking for the Node.js version? Give a look [here](https://github.com/RyanJ93/locale-kit).