# Command line tool documentation

The package "php-locale-kit" provides a built-in command line tool that allows you to manage translations, you can easily create and translate packages, before using this tool, you need to give execution permits by running "chmod +x php-locale-kit", then you can run it as following:

````bash
./php-locale-kit action [--options] [-params value] [path] [text] [locale] [original locale]
````

### Available actions

* **create**: Creates a new package (as SQLite database file), if it already exists, an error will be thrown, unless you set the "--overwrite" option.
* **translate-package**: Translates the package according to the given options.
* **translate**: Translates a given text to a given locale, optionally you can specify the language of the input text.
* **detect**: Detects the language of a given text.
* **list**: Returns a list of all the locales supported by the service provider in use.
* **help**: Displays the documentation page.
* **man**: Alias for "help".

### Available parameters

* **-token**: The API key for the selected provider, this is required to use every service provider.
* **-provider**: The name of the provider to use, by default, "Yandex" is used, currently supported providers are only "Google" (paid) and "Yandex" (10M chars/month free then paid), names are case-insensitive.
* **-locale**: The start locale code used in package translation, note that this locale must be supported by both the package and the provider.
* **-locales**: A list of the locales that must be translated, multiple locale codes can be separated by a comma, note that the specified locales must be supported by both the package and the service provider.
* **-skip-locales**: A list of the locales that must not be translated, multiple locale codes can be separated by a comma.
* **-chunk**: An integer number that specifies how many labels should be translated for each request, use 1 to send a single request for each label, by default 10 is used.
* **-original-locale**: The language of the given text, note that this option is considered only when using the actions "translate" and "detect".
* **-hints**: One or more locale codes that will be sent as probably locales for language detection, note that this option is considered only when using "detect" as action and Yandex as service provider, you can set multiple codes by separating them with a comma.
* **-ui**: The language in which the language names should be returned, this option is considered only when using the actions "list".
* **-labels**: One of more label IDs that will be translated instead of translating all the labels contained in the package.
* **-ignored-labels**: One of more label IDs that will not be translated.
* **-format**: A string containing the name of the format of the text contained within the package, currently HTML and text are the only formats supported, by default text is used.

### Available options

* **--string-ids**: Using this option it means that label identifiers are represented by strings instead of integer numbers.
* **--overwrite**: Using this option in package creation it means that if a package with the same path is already existing it will be overwritten, otherwise an error will be thrown.
* **--fill**: In package creation, with this option the table that contains the supported locales will be filled with all supported locales of the given provider, note that the default provider is Yandex.
* **--verbose**: Using this option, all errors will be logged to the console, this can be helpful in debug.
* **--override**: Using this option, locked locales and labels will be translated as well, otherwise they will be skipped.
* **--codes**: Using this option, when retrieving the list of all the languages supported by the provider, will be returned a list of locale codes, otherwise the list of the language names.

## Usage examples

Create a new package:

````bash
./php-locale-kit create package.db
````

Create a new package and add support for all locales supported by the service provider:

````bash
./php-locale-kit create -token "[YOUR GOOGLE OR YANDEX TOKEN]" --fill package.db
````

Translate all labels contained in a package using English as reference language:

````bash
./php-locale-kit translate-package -token "[YOUR GOOGLE OR YANDEX TOKEN]" -locale "en" package.db
````

Translate all labels corresponding to some given locales (it, ru) using English as reference language:

````bash
./php-locale-kit translate-package -token "[YOUR GOOGLE OR YANDEX TOKEN]" -locale "en" -locales "it,ru" package.db
````

Translate a text from English to Italian:

````bash
./php-locale-kit translate -token "[YOUR GOOGLE OR YANDEX TOKEN]" "A sample sentence right here." it
````

Detecting the language of a text:

````bash
./php-locale-kit detect -token "[YOUR GOOGLE OR YANDEX TOKEN]" "E la volpe con il suo balzò superò il quieto fido"
````

Retrieving a list of all the languages supported by Yandex:

````bash
./php-locale-kit list -token "[YOUR GOOGLE OR YANDEX TOKEN]"
````