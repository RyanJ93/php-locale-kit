<?php
/**
* A simple library that allows to manage language packages and translate texts with PHP.
*
* @package     php-locale-kit
* @author      Enrico Sola <info@enricosola.com>
* @version     v.1.1.0
*/

namespace PHPLocaleKit{
	class Package{
		/**
		* @const string VERSION A string containing the version of this library.
		*/
		const VERSION = '1.1.0';
		
		/**
		* @var string $path A string containing the path to the package.
		*/
		protected $path = NULL;
		
		/**
		* @var string $locale A string containing the locale code currently in use.
		*/
		protected $locale = NULL;
		
		/**
		* @var SQLite3 $connection An instance of the class "SQLite3" representing the connection with the package.
		*/
		protected $connection = NULL;
		
		/**
		* @var SQLite3 $cache An instance of the class "SQLite3" representing the connection with the package.
		*/
		protected $cache = false;
		
		/**
		* @var PHPTinyCacher $cacheHandler An instance of the class "PHPTinyCacher" from the package "php-tiny-cacher" representing the handler used to manage the data caching.
		*/
		protected $cacheHandler = NULL;
		
		/**
		* @var string $packageIdentifier A string containing the package identifier used in data caching.
		*/
		protected $packageIdentifier = NULL;
		
		/**
		* @var int $localeID An integer number greater than zero representing the ID of the locale found within the package given.
		*/
		protected $localeID = NULL;
		
		/**
		* @var bool $fallback If set to "true" it means that the current locale is a fallback derived from another locale that was given originally.
		*/
		protected $fallback = false;
		
		/**
		* @var bool $verbose If set to "true" error messages will be displayed, otherwise not.
		*/
		protected $verbose = false;
		
		/**
		* The class constructor.
		*
		* @param string $path A string containing the path to the SQLite database.
		* @param string $locale A string containing the locale code, alternatively, the language code will be extracted if the given locale code were not found within the database (en-US => en).
		* @param bool $strict If set to "true" only the locale code will be used, if not supported by the package will be thrown an exception instead of looking for the language code.
		*
		* @throws Exception If the given locale code and the relative language code were not found within the database.
		* @throws Exception If an error occurrs on the database side.
		*/
		public function __construct(string $path = NULL, string $locale = NULL, bool $strict = false){
			if ( $path !== NULL && $path !== '' && $locale !== NULL && $locale !== '' ){
				$this->setPackage($path, $locale, $strict);
			}
		}
		
		/**
		* Sets the parameters for the package that contains the labels, once the parameters have been set, a connection with the database will be done, this method is chainable.
		*
		* @param string $path A string containing the path to the SQLite database.
		* @param string $locale A string containing the locale code, alternatively, the language code will be extracted if the given locale code were not found within the database (en-US => en).
		* @param bool $strict If set to "true" only the locale code will be used, if not supported by the package will be thrown an exception instead of looking for the language code.
		* 
		* @return string A string containing the locale code that has been set, this can be useful when using fallback locales.
		*
		* @throws InvalidArgumentException If an invalid path is given.
		* @throws InvalidArgumentException If an invalid locale code is given.
		* @throws Exception If an error occurs while connecting to the package.
		* @throws Exception If an error occurs while setting the locale for the given package.
		*/
		public function setPackage(string $path, string $locale, bool $strict = false): Package{
			if ( $path === NULL || $path === '' ){
				throw new \InvalidArgumentException('Invalid path.');
			}
			if ( $locale === NULL || $locale === '' ){
				throw new \InvalidArgumentException('Invalid locale code.');
			}
			$verbose = $this->getVerbose();
			try{
				$this->setPath($path);
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('An error occurred while setting up the package.', NULL, $ex);
			}
			try{
				$this->setLocale($locale, $strict);
				return $this;
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('An error occurred while setting the locale.', NULL, $ex);
			}
		}
		
		/**
		* Sets the path to the database containing the labels, once a path has been set, a connection with the database will be done, this method is chainable.
		*
		* @param string $path A string containing the path to the SQLite database.
		* 
		* @throws InvalidArgumentException If an invalid path is given.
		* @throws Exception If an error occurrs while connecting to the package.
		*/
		public function setPath(string $path): Package{
			if ( $path === NULL || $path === '' ){
				throw new \InvalidArgumentException('Invalid path.');
			}
			$verbose = $this->getVerbose();
			$this->path = $path;
			$this->locale = NULL;
			$this->localeID = NULL;
			$this->fallback = false;
			$this->connection = NULL;
			try{
				$this->connectToPackage();
				return $this;
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('An error occurred while connecting to the package.', NULL, $ex);
			}
		}
		
		/**
		* Returns the path to the database containing the labels.
		*
		* @return string A string containing the path to the SQLite database or an empty string if no path has been set.
		*/
		public function getPath(): string{
			$path = $this->path;
			return $path === NULL ? '' : $path;
		}
		
		/**
		* Sets if errors shall be displayed in console or not, this method is chainable.
		*
		* @param bool $verbose If set to "true", every error will be displayed in console, otherwise not.
		*/
		public function setVerbose(bool $verbose = false): Package{
			$this->verbose = $verbose === true ? true : false;
			return $this;
		}
		
		/**
		* Returns if errors shall be displayed in console or not.
		*
		* @return bool If errors are going to be displayed in console will be returned "true", otherwise "false".
		*/
		public function getVerbose(): bool{
			return $this->verbose === true ? true : false;
		}
		
		/**
		* Sets if the labels read from the database shall be stored within the cache for next uses or not, this method is chainable.
		*
		* @param bool $cache If set to "true" results will be cached, otherwise not.
		*/
		public function setCache(bool $cache = false): Package{
			$this->cache = $cache === true ? true : false;
			return $this;
		}
		
		/**
		* Returns if the labels read from the database shall be stored within the cache for next uses or not.
		*
		* @return bool If results are going to be cached will be returned "true", otherwise "false".
		*/
		public function getCache(): bool{
			return $this->cache === true ? true : false;
		}
		
		/**
		* Sets the handler for cache, it must be an instance of the class "PHPTinyCacher" from the package "php-tiny-cacher", to unset the handler, pass "NULL" as parameter, this method is chainable.
		*
		* @param PHPTinyCacher $handler An instance of the class "PHPTinyCacher" representing that provides an API to handle data caching.
		*/
		public function setCacheHandler(\PHPTinyCacher\PHPTinyCacher $handler = NULL): Package{
			$this->cacheHandler = $handler;
			return $this;
		}
		
		/**
		* Returns the hadler used in result caching.
		*
		* @return PHPTinyCacher An instance of the class "PHPTinyCacher" from the package "php-tiny-cacher", if no handler has been defined, will be returned "NULL".
		*/
		public function getCacheHandler(){
			return $this->cacheHandler instanceof \PHPTinyCacher\PHPTinyCacher ? $this->cacheHandler : NULL;
		}
		
		/**
		* Returns if the cache handler is ready or not.
		*
		* @return bool If the cache is ready wil be returned "true", otherwise "false".
		*/
		public function cacheReady(): bool{
			return $this->getCache() === true && $this->cacheHandler instanceof \PHPTinyCacher\PHPTinyCacher && $this->cacheHandler->isReady() === true ? true : false;
		}
		
		/**
		* Tries to connect to the given database then set the connection within the class instance, this method is chainable.
		*
		* @throws BadMethodCallException If no path has been set.
		* @throws Exception If an error occurrs on the database side.
		* @throws Exception If an error occurs while trying to get the package identifier used for data caching.
		*/
		public function connectToPackage(): Package{
			$path = $this->getPath();
			if ( $path === '' ){
				throw new \BadMethodCallException('No path has been set.');
			}
			$verbose = $this->getVerbose();
			try{
				$this->connection = new \SQLite3($path, \SQLITE3_OPEN_READONLY);
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('Unable to connect to the package.', NULL, $ex);
			}
			try{
				$this->loadPackageIdentifier();
				return $this;
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('Unable to get the package identifier.', NULL, $ex);
			}
		}
		
		/**
		* Sets the package identifier used in data caching, note that the identifier will be not saved within the package file, this method is chainable.
		*
		* @param string $identifier A string containinig the package identifier, if set to NULL, no identifier will be used.
		*
		* @throws BadMethodCallException If no package has been defined.
		*/
		public function setPackageIdentifier(string $identifier = NULL): Package{
			if ( $this->connected() === false ){
				throw new \BadMethodCallException('No package has been defined.');
			}
			$this->packageIdentifier = $identifier !== NULL ? $identifier : '';
			return $this;
		}
		
		/**
		* Loads the identifier containined within the package file.
		*
		* @return string A string containing the identifier of the package.
		*
		* @throws BadMethodCallException If no package has been defined.
		* @throws Exception If an error occurrs on the database side.
		*/
		public function loadPackageIdentifier(): string{
			if ( $this->connected() === false ){
				throw new \BadMethodCallException('No package has been defined.');
			}
			$verbose = $this->getVerbose();
			try{
				$results = $this->connection->query('SELECT value FROM meta WHERE key = "identifier" LIMIT 1;');
				while ( $row = $results->fetchArray() ){
					return $this->packageIdentifier = is_string($row['value']) === true && $row['value'] !== '' ? $row['value'] : hash('md5', $this->path);
				}
				return $this->packageIdentifier = hash('md5', $this->path);
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('Unable to connect to the package.', NULL, $ex);
			}
		}
		
		/**
		* Returns the package identifier.
		*
		* @return string A string containing the package identifier, if no identifier were found, an empty string will be returned instead.
		*
		* @throws BadMethodCallException If no package has been defined.
		*/
		public function getPackageIdentifier(): string{
			if ( $this->connected() === false ){
				throw new \BadMethodCallException('No package has been defined.');
			}
			$packageIdentifier = $this->packageIdentifier;
			return $packageIdentifier === NULL ? '' : $packageIdentifier;
		}
		
		/**
		* Checks if a connection has been set within the class instance.
		*
		* @return bool If a connection were found will be returned "true", otherwise "false".
		*/
		public function connected(): bool{
			return $this->connection !== NULL && $this->connection instanceof \SQLite3 ? true : false;
		}
		
		/**
		* Returns all the locales and languages supported by the package.
		*
		* @return array A sequentiall array where every locale is represented by an associative array.
		*
		* @throws BadMethodCallException If not connection with the database were found.
		* @throws Exception If an error occurrs on the database side.
		*/
		public function getSupportedLocales(): array{
			if ( $this->connected() === false ){
				throw new \BadMethodCallException('No package has been defined.');
			}
			$verbose = $this->getVerbose();
			try{
				$results = $this->connection->query('SELECT lang, code, id, locked FROM locales;');
				$locales = array();
				while ( $row = $results->fetchArray() ){
					$locales[] = array(
						'language' => $row['lang'],
						'locale' => $row['code'],
						'id' => $row['id'],
						'locked' => $row['locked'] === true || $row['locked'] === 1 ? true : false
					);
				}
				return $locales;
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('Unable to connect to the package.', NULL, $ex);
			}
		}
		
		/**
		* Checks if a given locale is supported by the package or not.
		*
		* @param string $locale A string containing the locale code, alternatively, the language code will be extracted if the given locale code were not found within the database (en-US => en).
		* @param bool $strict If set to "true" only the locale code will be used, if not supported by the package will be returned "false".
		*
		* @return bool If the given locale is supported or there is a fallback language available for this locale (if strict is not set to "true") will be returned "true", otherwise "false".
		*
		* @throws InvalidArgumentException If an invalid locale is given.
		* @throws BadMethodCallException If not connection with the database were found.
		* @throws Exception If an error occurrs on the database side.
		*/
		public function isLocaleSupported(string $locale, bool $strict = false): bool{
			if ( $locale === NULL || $locale === '' ){
				throw new \InvalidArgumentException('Invalid locale code.');
			}
			if ( $this->connected() === false ){
				throw new \BadMethodCallException('No package has been defined.');
			}
			$verbose = $this->getVerbose();
			try{
				$statement = $this->connection->prepare('SELECT id FROM locales WHERE code = :code LIMIT 1;');
				$statement->bindValue('code', $locale, \SQLITE3_TEXT);
				$results = $statement->execute();
				while ( $row = $results->fetchArray() ){
					return true;
				}
				$index = strpos($locale, '-');
				if ( $index === false ){
					return false;
				}
				$locale = strtolower(substr($locale, 0, $index));
				$statement = $this->connection->prepare('SELECT id FROM locales WHERE lang = :lang LIMIT 1;');
				$statement->bindValue('lang', $locale, \SQLITE3_TEXT);
				$results = $statement->execute();
				while ( $row = $results->fetchArray() ){
					return true;
				}
				return false;
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('Unable to connect to the package.', NULL, $ex);
			}
		}
		
		/**
		* Sets the locale, the method will look for the given locale within the package that has been defined.
		*
		* @param string $locale A string containing the locale code, alternatively, the language code will be extracted if the given locale code were not found within the database (en-US => en).
		* @param bool $strict If set to "true" only the locale code will be used, if not supported by the package will be thrown an exception instead of looking for the language code.
		*
		* @return string A string containing the locale code that has been set, this can be useful when using fallback locales.
		*
		* @throws InvalidArgumentException If an invalid locale is given.
		* @throws BadMethodCallException If not connection with the database were found.
		* @throws Exception If an error occurrs on the database side.
		* @throws Exception If the given locale is not supported by the package that has been defined.
		*/
		public function setLocale(string $locale, bool $strict = true): string{
			if ( $locale === NULL || $locale === '' ){
				throw new \InvalidArgumentException('Invalid locale code.');
			}
			if ( $this->connected() === false ){
				throw new \BadMethodCallException('No package has been defined.');
			}
			$verbose = $this->getVerbose();
			try{
				$statement = $this->connection->prepare('SELECT id FROM locales WHERE code = :code LIMIT 1;');
				$statement->bindValue('code', $locale, \SQLITE3_TEXT);
				$results = $statement->execute();
				while ( $row = $results->fetchArray() ){
					$this->locale = $locale;
					$this->localeID = $row['id'];
					$this->fallback = false;
					return $locale;
				}
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('Unable to connect to the package.', NULL, $ex);
			}
			$index = strpos($locale, '-');
			if ( $index === false ){
				throw new \Exception('Unsupported locale.');
			}
			$locale = strtolower(substr($locale, 0, $index));
			try{
				$statement = $this->connection->prepare('SELECT id FROM locales WHERE lang = :lang LIMIT 1;');
				$statement->bindValue('lang', $locale, \SQLITE3_TEXT);
				$results = $statement->execute();
				while ( $row = $results->fetchArray() ){
					$this->locale = $locale;
					$this->localeID = $row['id'];
					$this->fallback = false;
					return $locale;
				}
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('Unable to connect to the package.', NULL, $ex);
			}
			throw new \Exception('Unsupported locale.');
		}
		
		/**
		* Returns the locale that has been set.
		*
		* @return string A string containing the locale code, if no locale has been defined, an empty string will be returned instead.
		*/
		public function getLocale(): string{
			$locale = $this->locale;
			return $locale === NULL ? '' : $locale;
		}
		
		/**
		* Returns if the current locale is a fallback obtained from the original locale or not, for example, if "en-US" has been specified but "en" or another variant like "en-UK" has been picked instead.
		*
		* @return bool If current locale is a fallback will be returned "true", otherwsie "false".
		*/
		public function isFallback(): bool{
			return $this->fallback ? true : false; 
		}
		
		/**
		* Returns the labels from the package set.
		*
		* @param array $labels A sequentiall array containing the identifiers of the labels that shall be returned, a number or a string can be used and internally will be converted in a single element array.
		* @param bool $fresh If set to "true" all labels will be readed from the package without looking for them into the cache, otherwise the cache will be queried first.
		*
		* @return array An associative array containing as key the label ID and as value the label itself.
		*
		* @throws InvalidArgumentException If an invalid array is given.
		* @throws BadMethodCallException If not connection with the database were found.
		* @throws BadMethodCallException If no locale has previously been defined.
		* @throws Exception If an error occurrs on the database side.
		* @throws Exception If an error occurrs while fetching data from the cache.
		* @throws Exception If an error occurrs while saving the data into the cache.
		*/
		public function getLabels(array $labels, bool $fresh = false): array{
			if ( $labels === NULL ){
				throw new \InvalidArgumentException('Invalid labels.');
			}
			if ( $this->connected() === false ){
				throw new \BadMethodCallException('No package has been defined.');
			}
			$localeID = $this->localeID;
			if ( $localeID === NULL || $localeID <= 0 || is_int($localeID) === false ){
				throw new \BadMethodCallException('No locale defined.');
			}
			$verbose = $this->getVerbose();
			foreach ( $labels as $key => $value ){
				if ( $value === '' || $value <= 0 || ( is_string($value) === false && is_int($value) === false ) ){
					throw new \InvalidArgumentException('Invalid labels.');
				}
			}
			$data = array();
			$identifier = $this->getPackageIdentifier();
			if ( $identifier !== '' ){
				$identifier .= ':';
			}
			if ( $fresh !== true && $this->cacheReady() === true ){
				$keys = array();
				foreach ( $labels as $key => $value ){
					$keys['PHPLocaleKit.lbl:' . $identifier . $localeID . ':' . ( is_string($value) === true ? hash('md5', $value) : $value )] = $value;
				}
				try{
					$elements = $this->cacheHandler->pullMulti(array_keys($keys), true);
				}catch(\Exception $ex){
					if ( $verbose === true ){
						echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
					}
					throw new \Exception('An error occurred while fetching data from the cache.', NULL, $ex);
				}
				$missing = array();
				$all = true;
				foreach ( $elements as $key => $value ){
					if ( $value === NULL || is_string($value) === false ){
						$missing[$key] = $keys[$key];
						$all = false;
						continue;
					}
					$data[$keys[$key]] = $value;
				}
				if ( $all === true ){
					return $data;
				}
				$query = '';
				$labels = array_values($missing);
				foreach ( $labels as $key => $value ){
					$query .= $query === '' ? '?' : ',?';
				}
				try{
					$statement = $this->connection->prepare('SELECT id, value FROM labels WHERE locale = ? AND id IN (' . $query . ');');
					$statement->bindValue(1, $localeID, \SQLITE3_INTEGER);
					foreach ( $labels as $key => $value ){
						if ( is_string($value) === true ){
							$statement->bindValue(( $key + 2 ), $value, \SQLITE3_TEXT);
							continue;
						}
						$statement->bindValue(( $key + 2 ), $value, \SQLITE3_INTEGER);
					}
					$results = $statement->execute();
					$keys = array_flip($keys);
					$buffer = array();
					$empty = true;
					while ( $row = $results->fetchArray() ){
						$data[$row['id']] = $row['value'];
						$buffer[$keys[$row['id']]] = $row['value'];
						$empty = false;
					}
					if ( $empty === true || $this->cacheReady() === false ){
						return $data;
					}
				}catch(\Exception $ex){
					if ( $verbose === true ){
						echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
					}
					throw new \Exception('An error occurred during database transaction.', NULL, $ex);
				}
				try{
					$this->cacheHandler->pushMulti($buffer, true);
					return $data;
				}catch(\Exception $ex){
					if ( $verbose === true ){
						echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
					}
					throw new \Exception('An error occurred while saving elements within the cache.', NULL, $Ex);
				}
			}
			try{
				$query = '';
				foreach ( $labels as $key => $value ){
					$query .= $query === '' ? '?' : ',?';
				}
				$statement = $this->connection->prepare('SELECT id, value FROM labels WHERE locale = ? AND id IN (' . $query . ');');
				$statement->bindValue(1, $localeID, \SQLITE3_INTEGER);
				foreach ( $labels as $key => $value ){
					if ( is_string($value) === true ){
						$statement->bindValue(( $key + 2 ), $value, \SQLITE3_TEXT);
						continue;
					}
					$statement->bindValue(( $key + 2 ), $value, \SQLITE3_INTEGER);
				}
				$results = $statement->execute();
				while ( $row = $results->fetchArray() ){
					$data[$row['id']] = $row['value'];
				}
				return $data;
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('An error occurred during database transaction.', NULL, $ex);
			}
		}
		
		/**
		* Returns all labels matching the locale that has been set, note that cache will not be used because no label ID is specified and reading IDs from the package breaks the reason of using cache..
		*
		* @return array An associative array containing as key the label ID and as value the label itself.
		*
		* @throws BadMethodCallException If not connection with the database were found.
		* @throws BadMethodCallException If no locale has previously been defined.
		* @throws exception If an error occurrs on the database side.
		* @throws exception If an error occurrs while fetching data from the cache.
		* @throws exception If an error occurrs while saving the data into the cache.
		*/
		public function getAllLabels(): array{
			if ( $this->connected() === false ){
				throw new \BadMethodCallException('Not connected to the package.');
			}
			$localeID = $this->localeID;
			if ( $localeID === NULL || $localeID <= 0 || is_int($localeID) === false ){
				throw new \BadMethodCallException('No locale defined.');
			}
			$verbose = $this->getVerbose();
			try{
				$statement = $this->connection->prepare('SELECT id, value FROM labels WHERE locale = :locale;');
				$statement->bindValue(1, $localeID, \SQLITE3_INTEGER);
				$data = array();
				while ( $row = $results->fetchArray() ){
					$data[$row['id']] = $row['value'];
				}
				return $data;
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('An error occurred during database transaction.', NULL, $ex);
			}
		}
	}
	
	class Translator{
		/**
		* @const string VERSION A string containing the version of this library, alias for "Package::VERSION".
		*/
		const VERSION = '1.1.0';
		
		/**
		* @const int YANDEX Specifies that Yandex.Translate must be used as service provider, more information about this provider here: https://translate.yandex.com/developers
		*/
		const YANDEX = 1;
		
		/**
		* @const int GOOGLE Specifies that Google Cloud Translation must be used as service provider, more information about this provider here: https://cloud.google.com/translate/
		*/
		const GOOGLE = 2;
		
		/**
		* @const int MICROSOFT Specifies that the Microsoft Azure Translator Text APIs must be used as service provider, more information about this provider here: https://azure.microsoft.com/en-us/services/cognitive-services/translator-text-api/
		*/
		const MICROSOFT = 3;
		
		/**
		* @const int TEXT Specifies that the given texts must be handled as plain text.
		*/
		const TEXT = 1;
		
		/**
		* @const int HTML Specifies that the given texts must be handled as HTML code.
		*/
		const HTML = 2;
		
		/**
		* @const int NEURAL_MACHINE_TRANSLATION Specifies that the Neural Machine Translation (nmt) model shall be used in text translation, this option is supported by Google only, more information here: https://cloud.google.com/translate/docs/reference/translate
		*/
		const NEURAL_MACHINE_TRANSLATION = 1;
		
		/**
		* @const int PHRASE_BASED_MACHINE_TRANSLATION Specifies that the Phase Based Machine Translation (pbmt) model shall be used in text translation, this option is supported by Google only, more information here: https://cloud.google.com/translate/docs/reference/translate
		*/
		const PHRASE_BASED_MACHINE_TRANSLATION = 2;
		
		/**
		* @const int PROFANITY_NO_ACTION Specifies that no action shall be done if a text contains profanity, this option is supported by Microsoft only, more information here: http://docs.microsofttranslator.com/text-translate.html 
		*/
		const PROFANITY_NO_ACTION = 1;
		
		/**
		* @const int PROFANITY_MARKED Specifies that if a text contains profanity, the part must be marked using an HTML-like tag called "<profanity>", this option is supported by Microsoft only, more information here: http://docs.microsofttranslator.com/text-translate.html 
		*/
		const PROFANITY_MARKED = 2;
		
		/**
		* @const int PROFANITY_DELETED Specifies that if a text contains profanity, the part must be removed from the text, this option is supported by Microsoft only, more information here: http://docs.microsofttranslator.com/text-translate.html 
		*/
		const PROFANITY_DELETED = 3;
		
		/**
		* @var int $provider An integer number that contains the service provider in use, by default Yandex is used.
		*/
		protected $provider = 1;
		
		/**
		* @var string $token A string containing the API token that will be sent to the provider in order to use its services.
		*/
		protected $token = NULL;
		
		/**
		* @var int $textFormat An integer number that contains the format of the texts that will be translated.
		*/
		protected $textFormat = 1;
		
		/**
		* @var int $translationModel An integer number that contains the translation model to use in text translation, this option is supported by Google only.
		*/
		protected $translationModel = 1;
		
		/**
		* @var int $profanityHandling An integer number that specifies how profinity should be handled during translations, this option is supported by Microsoft only.
		*/
		protected $profanityHandling = 1;
		
		/**
		* @var SQLite3 $cache An instance of the class "SQLite3" representing the connection with the package.
		*/
		protected $cache = false;
		
		/**
		* @var PHPTinyCacher $cacheHandler An instance of the class "PHPTinyCacher" from the package "php-tiny-cacher" representing the handler used to manage the data caching.
		*/
		protected $cacheHandler = NULL;
		
		/**
		* @var bool $verbose If set to "true" error messages will be displayed, otherwise not.
		*/
		protected $verbose = false;
		
		/**
		* Sends an HTTPS request to a given URL.
		*
		* @param string $url A string containing the URL.
		* @param array $params An object containing the additional parameters that shall be sent as POST parameters.
		* 
		* @return string A string containing the response returned by the server.
		*
		* @throws InvalidArgumentException If an invalid URL is given.
		* @throws Exception If the request fails.
		*/
		protected static function sendRequest(string $url, array $params): string{
			if ( $url === NULL || $url === '' ){
				throw new \InvalidArgumentException('Invalid URL.');
			}
			$buffer = array();
			foreach ( $params as $key => $value ){
				if ( is_array($value) === true ){
					foreach ( $value as $_key => $_value ){
						$buffer[] = urlencode($key) . '=' . urlencode($_value);
					}
					continue;
				}
				$buffer[] = urlencode($key) . '=' . urlencode($value);
			}
			$request = curl_init();
			curl_setopt_array($request, array(
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => implode('&', $buffer),
				CURLOPT_URL => $url,
				CURLOPT_RETURNTRANSFER => true
			));
			$data = curl_exec($request);
			$code = curl_errno($request);
			if ( $code !== 0 ){
				throw new \Exception('Request failed with message "' . curl_error($request) . '" (' . $code . ').');
			}
			return $data;
		}
		
		/**
		* Returns the exception message according with the status code returned by the provider.
		*
		* @param int $code An integer number greather than zero representing the status code returned by the service provider.
		* @param int $provider An integer number representing the service provider in use.
		*
		* @return string A string containing the error description used to throw the exception.
		*/
		protected static function getErrorMessageByErrorCode(int $code, int $provider): string{
			switch ( $provider ){
				case self::GOOGLE:{
					switch ( $code ){
						default:{
							return 'Unexpected error from Google (' . $code . ').';
						}break;
					}
				}break;
				default:{
					switch ( $code ){
						case 401:{
							return 'The API key that has been set within the class instance is not valid.';
						}break;
						case 402:{
							return 'The API key that has been set within the class has been rejected by Yandex.';
						}break;
						case 404:{
							return 'Your translate limit has expired, you need to upgrade your plan or wait for limit reset.';
						}break;
						case 413:{
							return 'The provided text is too long.';
						}break;
						case 422:{
							return 'The provided text cannot be translated.';
						}break;
						case 501:{
							return 'The specified translation direction is not supported.';
						}break;
						default:{
							return 'Unexpected error from Yandex (' . $code . ').';
						}break;
					}
				}break;
			}
		}
		
		/**
		* Translates the given texts, this method is called internally by the method "translateText".
		*
		* @param array $elements A sequentiall array of strings containing the texts that will be translated.
		* @param string $locale A string containing the code of the locale that the texts shall be traslated in.
		* @param string $originalLocale A string containing code of the locale of the given texts, if omitted it will be automatically detected by the service provider.
		*
		* @return array An associative array of strings containing as key the original text and as value the translated text.
		*
		* @throws Exception If an error occurs during the HTTP request.
		* @throws Exception If an invalid response is received from the provider.
		* @throws Exception If the Yandex API key that has been set is not valid according to Yandex.
		* @throws Exception If the Yandex API key that has been set has been blocked by Yandex.
		* @throws Exception If daily limit of translatable chars of the Yandex API key has been reached.
		* @throws Exception If the given text cannot be translated by Yandex.
		* @throws Exception If the translation direction is not supported by Yandex APIs.
		* @throws Exception If the request fails at Google side.
		*/
		protected function translateElements(array $elements, string $locale, string $originalLocale = NULL): array{
			if ( $elements === NULL || $locale === NULL || $locale === '' ){
				return array();
			}
			$provider = $this->getProvider();
			$verbose = $this->getVerbose();
			try{
				switch ( $provider ){
					case self::GOOGLE:{
						$results = self::sendRequest('https://translation.googleapis.com/language/translate/v2', array(
							'text' => $elements,
							'lang' => $originalLocale !== NULL && $originalLocale !== '' ? ( $originalLocale . '-' . $locale ) : $locale,
							'format' => $this->getTextFormatName(),
							'key' => $this->getToken()
						));
					}break;
					default:{
						$results = self::sendRequest('https://translate.yandex.net/api/v1.5/tr.json/translate', array(
							'text' => $elements,
							'lang' => $originalLocale !== NULL && $originalLocale !== '' ? ( $originalLocale . '-' . $locale ) : $locale,
							'format' => strtolower($this->getTextFormatName()),
							'key' => $this->getToken()
						));
					}break;
				}
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('Unable to complete the request.', NULL, $ex);
			}
			if ( $results === '' ){
				throw new \Exception('Invalid response from the provider.');
			}
			$results = json_decode($results, true);
			if ( $results === NULL ){
				throw new \Exception('Invalid response from the provider.');
			}
			$ret = array();
			switch ( $provider ){
				case self::GOOGLE:{
					if ( isset($results['data']) === false || is_array($results['data']) === false || isset($results['data']['translations']) === false || is_array($results['data']['translations']) === false || isset($results['data']['translations'][0]) === false ){
						throw new \Exception('Invalid response from Google.');
					}
					foreach ( $elements as $key => $value ){
						$ret[$value] = isset($results['data']['translations'][$key]['translatedText']) === true && is_string($results['data']['translations'][$key]['translatedText']) === true ? $results['data']['translations'][$key]['translatedText'] : NULL;
					}
				}break;
				default:{
					if ( isset($results['code']) === false || $results['code'] !== 200 ){
						throw new \Exception(isset($results['code']) === false || is_int($results['code']) === false ? 'Invalid response from Yandex.' : self::getErrorMessageByErrorCode($results['code'], 1));
					}
					if ( isset($results['text']) === false || is_array($results['text']) === false ){
						throw new \Exception('Invalid response from Yandex.');
					}
					foreach ( $elements as $key => $value ){
						$ret[$value] = isset($results['text'][$key]) === true && is_string($results['text'][$key]) === true ? $results['text'][$key] : NULL;
					}
				}break;
			}
			return $ret;
		}
		
		/**
		* Detects the language of a given texts, this method is used internally by the method "detectLanguage".
		*
		* @param array $text A sequentiall array of strings that contains the texts that shall be analyzed.
		* @param array $hints An optional sequentiall array of strings containing the codes of the languages of which the text is expected to be, note that this option is supported by Yandex only.
		*
		* @return array An associative array of strings containing as key the original text and as value the detected langauge code or null if no language has been found.
		*
		* @throws Exception If an error occurs during the HTTP request.
		* @throws Exception If an invalid response is received from the provider.
		* @throws Exception If the Yandex API key that has been set is not valid according to Yandex.
		* @throws Exception If the Yandex API key that has been set has been blocked by Yandex.
		* @throws Exception If daily limit of translatable chars of the Yandex API key has been reached.
		* @throws Exception If the request fails at Google side.
		*/
		protected function detectElementsLanguage(array $text, array $hints): array{
			if ( $text === NULL ){
				return array();
			}
			$hints = $hints === NULL || isset($hints[0]) === false ? '' : implode(',', $hints);
			$provider = $this->getProvider();
			$verbose = $this->getVerbose();
			$token = $this->getToken();
			$data = array();
			if ( $provider === self::YANDEX ){
				//Currently Yandex seems to not support multiple text detection within a single request, we need to do multiple requests.
				foreach ( $text as $key => $value ){
					try{
						$results = self::sendRequest('https://translate.yandex.net/api/v1.5/tr.json/detect', array(
							'key' => $token,
							'text' => $value,
							'hints' => $hints
						));
					}catch(\Exception $ex){
						if ( $verbose === true ){
							echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
						}
						throw new \Exception('Unable to complete the request.', NULL, $ex);
					}
					if ( $results === '' ){
						throw new \Exception('Invalid response from the provider.');
					}
					$results = json_decode($results, true);
					if ( $results === NULL ){
						throw new \Exception('Invalid response from the provider.');
					}
					if ( isset($results['code']) === false || $results['code'] !== 200 ){
						throw new \Exception(isset($results['code']) === false || is_int($results['code']) === false ? 'Invalid response from Yandex.' : self::getErrorMessageByErrorCode($results['code'] , 1));
					}
					$data[$value] = isset($results['lang']) === false || is_string($results['lang']) === false || $results['lang'] === '' ? NULL : $results['lang'];
				}
				return $data;
			}
			try{
				switch ( $provider ){
					case self::GOOGLE:{
						$results = self::sendRequest('https://translate.yandex.net/api/v1.5/tr.json/translate', array(
							'request' => $text,
							'key' => $token
						));
					}break;
				}
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('Unable to complete the request.', NULL, $ex);
			}
			if ( $results === '' ){
				throw new \Exception('Invalid response from the provider.');
			}
			$results = json_decode($results, true);
			if ( $results === NULL ){
				throw new \Exception('Invalid response from the provider.');
			}
			switch ( $provider ){
				case self::GOOGLE:{
					if ( isset($results['data']) === false || isset($results['data']['detections']) === false || is_array($results['data']['detections']) === false || isset($results['data']['detections'][0]) === false ){
						throw new \Exception('Invalid response from Google.');
					}
					foreach ( $text as $key => $value ){
						$data[$value] = isset($results['data']['detections'][$key]) === true && is_string($results['data']['detections'][$key]) === true && $results['data']['detections'][$key] !== '' ? $results['data']['detections'][$key] : NULL;
					}
				}break;
			}
			return $data;
		}
		
		/**
		* Returns all supported providers.
		*
		* @param bool $numeric If set to "true" will be returned a sequentiall array contianing the identifiers of the provers as integer numbers, otherwise as strings.
		*
		* @return array A sequentiall array of strings containing the provers identifiers.
		*/
		public static function getSupportedProviders(bool $numeric = false): array{
			return $numeric === true ? array(self::GOOGLE, self::MICROSOFT, self::YANDEX) : array('google', 'microsoft', 'yandex');
		}
		
		/**
		* Checks if a given provider is supported.
		*
		* @param string|int $provider A string containing the provider name, alternatively you could use one of the predefined constants.
		*
		* @return bool If the given provider is supported will be returned "true", otherwise "false".
		*/
		public static function supportedProvider($provider): bool{
			if ( is_string($provider) === true ){
				return in_array(strtolower($provider), self::getSupportedProviders(false)) === true ? true : false;
			}
			return in_array($provider, self::getSupportedProviders(true)) === true ? true : false;
		}
		
		/**
		* The class constructor.
		*
		* @param string|int $provider A string containing the provider name, alternatively you could use one of the predefined constants, currently only "yandex" and "google" are supported, by default "yandex" is used.
		* @param string $token A string containing the token.
		*/
		public function __construct($provider = NULL, string $token = NULL){
			if ( ( is_string($provider) === true || is_int($provider) === true ) && is_string($token) === true ){
				$this->setProvider($provider)->setToken($token);
			}
		}
		
		/**
		* Sets the provider to use for translations, this method is chainable.
		*
		* @param string|int $provider A string containing the provider name, alternatively you could use one of the predefined constants, currently only "yandex" and "google" are supported, by default "yandex" is used.
		*/
		public function setProvider($provider): Translator{
			switch ( ( is_string($provider) === true ? strtolower($provider) : $provider ) ){
				case self::GOOGLE:
				case 'google':{
					$this->provider = self::GOOGLE;
				}break;
				case self::MICROSOFT:
				case 'microsoft':{
					$this->provider = 3;
				}break;
				default:{
					$this->provider = self::YANDEX;
				}break;
			}
			return $this;
		}
		
		/**
		* Returns the provider to use for translations as numeric code, use "getProviderName" to get the provider name.
		*
		* @return int An integer number representing the provier.
		*/
		public function getProvider(): int{
			$provider = $this->provider;
			return is_int($provider) === true && ( $provider === self::GOOGLE || $provider === self::MICROSOFT ) ? $provider : self::YANDEX;
		}
		
		/**
		* Returns the provider to use for translations.
		*
		* @return string A string containing the provider name, by default "yandex" is used.
		*/
		public function getProviderName(): string{
			switch ( $this->getProvider() ){
				case self::GOOGLE:{
					return 'google';
				}break;
				case self::MICROSOFT:{
					return 'microsoft';
				}break;
				default:{
					return 'yandex';
				}break;
			}
		}
		
		/**
		* Sets the API token to use while querying the translation APIs (independently by the provider), this method is chainable.
		*
		* @param string $token A string containing the token.
		*
		* @throws InvalidArgumentException If an invalid token is given.
		*/
		public function setToken($token): Translator{
			//TODO: Add regex validation according to token format for each service provider.
			if ( $token === NULL || $token === '' ){
				throw new \InvalidArgumentException('Invalid token.');
			}
			$this->token = $token;
			return $this;
		}
		
		/**
		* Returns the API token.
		*
		* @return string A string containing the token.
		*
		* @throws BadMethodCallException If no token has been defined.
		*/
		public function getToken(): string{
			$token = $this->token;
			if ( $token === NULL || $token === '' || is_string($token) === false ){
				throw new \BadMethodCallException('No token defined.');
			}
			return $token;
		}
		
		/**
		* Sets the format of the texts that will be translated, this method is chainable.
		*
		* @param string|int $format A string containing the format name, alternatively you could use one of the predefined constants, supported formats are "text" and "HTML", if no valid format is given, "text" will be used.
		*/
		public function setTextFormat($format = 1): Translator{
			switch ( ( is_string($format) === true ? strtolower($format) : $format ) ){
				case self::HTML:
				case 'html':{
					$this->textFormat = self::HTML;
				}break;
				default:{
					$this->textFormat = self::TEXT;
				}break;
			}
			return $this;
		}
		
		/**
		* Returns the format of the texts that will be translated as numeric code, use "getTextFormatName" to get the format name.
		*
		* @return int An integer number representing the format.
		*/
		public function getTextFormat(): int{
			return $this->textFormat === self::HTML ? self::HTML : self::TEXT;
		}
		
		/**
		* Returns the format of the texts that will be translated.
		*
		* @return string A string containing the format name, by default "text" is used.
		*/
		public function getTextFormatName(): string{
			switch ( $this->getTextFormat() ){
				case self::HTML:{
					return 'HTML';
				}break;
				default:{
					return 'text';
				}break;
			}
		}
		
		/**
		* Sets the algorithm to use in translation, note that this option is supported by Google only, this method is chainable.
		*
		* @param int $translationModel An integer number representing the algorithm, you should use one of the predefined constants, by default "Neural Machine Translation" is used.
		*/
		public function setTranslationModel(int $translationModel = 1): Translator{
			switch ( $translationModel ){
				case 2:{
					$this->translationModel = self::PHRASE_BASED_MACHINE_TRANSLATION;
				}break;
				default:{
					$this->translationModel = self::NEURAL_MACHINE_TRANSLATION;
				}break;
			}
			return $this;
		}
		
		/**
		* Returns the algorithm to use in translation.
		*
		* @return int An integer number representing the algorithm.
		*/
		public function getTranslationModel(): int{
			return $this->translationModel === self::PHRASE_BASED_MACHINE_TRANSLATION ? self::PHRASE_BASED_MACHINE_TRANSLATION : self::NEURAL_MACHINE_TRANSLATION;
		}
		
		/**
		* Returns the name of the algorithm to use in translation.
		*
		* @return string A string containing the algorithm name.
		*/
		public function getTranslationModelName(): string{
			switch ( $this->getTranslationModel() ){
				case self::PHRASE_BASED_MACHINE_TRANSLATION:{
					return 'Phrase-Based Machine Translation';
				}break;
				default:{
					return 'Neural Machine Translation';
				}break;
			}
		}
		
		/**
		* Returns the code of the algorithm to use in translation.
		*
		* @return string A string containing the algorithm code.
		*/
		public function getTranslationModelCode(): string{
			switch ( $this->getTranslationModel() ){
				case self::PHRASE_BASED_MACHINE_TRANSLATION:{
					return 'pbmt';
				}break;
				default:{
					return 'nmt';
				}break;
			}
		}
		
		/**
		* Sets how profanity should be handled by the provider during text translation, note that this option is supported by Microsoft only, this method is chainable.
		*
		* @param int $profanityHandling An integer number greater or equal than one and lower or equal than 3, you should use on of the predefined constants.
		*/
		public function setProfanityHandling(int $profanityHandling = 1): Translator{
			$this->profanityHandling = $profanityHandling !== self::PROFANITY_MARKED && $profanityHandling !== self::PROFANITY_DELETED ? self::PROFANITY_NO_ACTION : $profanityHandling;
			return $this;
		}
		
		/**
		* Returns how profanity should be handled by the provider during text translation.
		*
		* @return int An integer number greater or equal than one and lower or equal than 3.
		*/
		public function getProfanityHandling(): int{
			$profanityHandling = $this->profanityHandling;
			if ( is_int($profanityHandling) === false ){
				return self::PROFANITY_NO_ACTION;
			}
			return $profanityHandling === self::PROFANITY_MARKED || $profanityHandling === self::PROFANITY_DELETED ? $profanityHandling : self::PROFANITY_NO_ACTION;
		}
		
		/**
		* Sets if errors shall be displayed in console or not, this method is chainable.
		*
		* @param bool $verbose If set to "true", every error will be displayed in console, otherwise not.
		*/
		public function setVerbose(bool $verbose = false): Translator{
			$this->verbose = $verbose === true ? true : false;
			return $this;
		}
		
		/**
		* Returns if errors shall be displayed in console or not.
		*
		* @return bool If errors are going to be displayed in console will be returned "true", otherwise "false".
		*/
		public function getVerbose(): bool{
			return $this->verbose === true ? true : false;
		}
		
		/**
		* Sets if results will be stored within the cache for next uses, this method is chainable.
		*
		* @param bool $cache If set to "true" results will be cached, otherwise not.
		*/
		public function setCache(bool $cache = false): Translator{
			$this->cache = $cache === true ? true : false;
			return $this;
		}
		
		/**
		* Returns if results will be stored within the cache for next uses or not.
		*
		* @return bool If results are going to be cached will be returned "true", otherwise "false".
		*/
		public function getCache(): bool{
			return $this->cache === true ? true : false;
		}
		
		/**
		* Sets the handler for cache, it must be an instance of the class "PHPTinyCacher" from the package "php-tiny-cacher", to unset the handler, pass "NULL" as parameter, this method is chainable.
		*
		* @param PHPTinyCacher $handler An instance of the class "PHPTinyCacher" representing that provides an API to handle data caching.
		*/
		public function setCacheHandler(\PHPTinyCacher\PHPTinyCacher $handler = NULL): Translator{
			$this->cacheHandler = $handler;
			return $this;
		}
		
		/**
		* Returns the hadler used in result caching.
		*
		* @return PHPTinyCacher An instance of the class "PHPTinyCacher" from the package "php-tiny-cacher", if no handler has been defined, will be returned "NULL".
		*/
		public function getCacheHandler(){
			return $this->cacheHandler instanceof \PHPTinyCacher\PHPTinyCacher ? $this->cacheHandler : NULL;
		}
		
		/**
		* Returns if the cache handler is ready or not.
		*
		* @return bool If the cache is ready wil be returned "true", otherwise "false".
		*/
		public function cacheReady(): bool{
			return $this->getCache() === true && $this->cacheHandler instanceof \PHPTinyCacher\PHPTinyCacher && $this->cacheHandler->isReady() === true ? true : false;
		}
		
		/**
		* Sets up the class instance to use Yandex Translate APIs for translations, this method is chainable.
		*
		* @param string $token A string containing the API token.
		* @param string|int $format An optional integer number representing the format of the texts that will be translated, alternatively you could use one of the predefined constants, by default "text" is used.
		*
		* @throws InvalidArgumentException If an invalid token is given.
		*/
		public function setupYandex(string $token, $format = 1): Translator{
			if ( $token === NULL || $token === '' ){
				throw new \InvalidArgumentException('No token defined.');
			}
			$this->token = $token;
			$this->provider = self::YANDEX;
			$this->setTextFormat($format);
			return $this;
		}
		
		/**
		* Sets up the class instance to use Google Cloud Translation APIs for translations, this method is chainable.
		*
		* @param string $token A string containing the API token.
		* @param string|int $format An optional integer number representing the format of the texts that will be translated, alternatively you could use one of the predefined constants, by default "text" is used.
		* @param int $translationModel An optional integer number representing the algorithm to use in translations, you should use one of the predefined constants, by default "Neural Machine Translation" is used.
		*
		* @throws InvalidArgumentException If an invalid token is given.
		*/
		public function setupGoogle(string $token, $format = 1, int $translationModel = 1): Translator{
			if ( $token === NULL || $token === '' ){
				throw new \InvalidArgumentException('No token defined.');
			}
			$this->token = $token;
			$this->provider = self::GOOGLE;
			$this->setTextFormat($format);
			$this->setTranslationModel($translationModel);
			return $this;
		}
		
		/**
		* Translates a given text or multiple texts.
		*
		* @param string|array $text A string containing the text that shall be translated, alternatively you can pass an array of strings, if Yandex is in used as provider, the string cannot be over than 10000 chars length
		* @param string $locale A string containing the locale code of the language that the text shall be translated in.
		* @param string $originalLocale A string containing the locale code of the text's language, if not set, it will be automatically detected by the provider.
		* @param bool $fresh If set to "true" all translations will be made by requesting data directly to the provider ignoring cache, otherwise, if the cached has been configured, data will be searched in cache before doing the requests.
		*
		* @return array An associative array of strings containing as key the original text and as value the translated one.
		*
		* @throws InvalidArgumentException If an invalid text is given.
		* @throws InvalidArgumentException If the given text is over 10000 chars length, note that this exception can be thrown only when using Yandex as provider.
		* @throws InvalidArgumentException If an invalid locale code is given as target language.
		* @throws BadMethodCallException If no token has previously been defined.
		* @throws Exception If an error occurs during the HTTP request.
		* @throws Exception If an invalid response is received from the provider.
		* @throws Exception If the Yandex API key that has been set is not valid according to Yandex.
		* @throws Exception If the Yandex API key that has been set has been blocked by Yandex.
		* @throws Exception If daily limit of translatable chars of the Yandex API key has been reached.
		* @throws Exception If the given text cannot be translated by Yandex.
		* @throws Exception If the translation direction is not supported by Yandex APIs.
		* @throws Exception If the request fails at Google side.
		* @throws Exception If an error occurrs while fetching data from the cache.
		* @throws Exception If an error occurrs while saving the data into the cache.
		*/
		public function translateText($text, string $locale, string $originalLocale = NULL, bool $fresh = false): array{
			if ( $locale === NULL || $locale === '' ){
				throw new \InvalidArgumentException('Invalid locale code.');
			}
			if ( $this->getToken() === '' ){
				throw new \BadMethodCallException('No token has been defined.');
			}
			if ( is_array($text) === false ){
				$text = array($text);
			}
			$provider = $this->getProvider();
			$verbose = $this->getVerbose();
			foreach ( $text as $key => $value ){
				if ( $value === NULL || $value === '' || is_string($value) === false ){
					unset($text[$key]);
					continue;
				}
				if ( $provider === self::YANDEX && mb_strlen($value, 'UTF-8') > 10000 ){
					throw new \InvalidArgumentException('The given text is too long.');
				}
			}
			$text = array_values(array_unique($text));
			if ( isset($text[0]) === false ){
				throw new \InvalidArgumentException('No valid text were found.');
			}
			$data = array();
			if ( $fresh !== true && $this->cacheReady() === true ){
				$keys = array();
				foreach ( $text as $key => $value ){
					$keys['PHPLocaleKit.translate:' . $locale . ':' . hash('md5', $value)] = $value;
				}
				try{
					$elements = $this->cacheHandler->pullMulti(array_keys($keys), true);
				}catch(\Exception $ex){
					if ( $verbose === true ){
						echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
					}
					throw new \Exception('An error occurred while fetching data from the cache.', NULL, $ex);
				}
				$text = array();
				foreach ( $elements as $key => $value ){
					if ( $value === NULL || is_string($value) === false ){
						$text[] = $keys[$key];
						continue;
					}
					$data[$keys[$key]] = $value;
				}
				if ( isset($text[0]) === false ){
					return $data;
				}
				try{
					$results = $this->translateElements($text, $locale, $originalLocale);
				}catch(\Exception $ex){
					throw new \Exception($ex->getMessage());
				}
				$keys = array_flip($keys);
				$save = array();
				foreach ( $results as $key => $value ){
					$save[$keys[$key]] = $value;
					$data[$key] = $value;
				}
				try{
					$this->cacheHandler->pushMulti($save, true);
					return $data;
				}catch(\Exception $ex){
					if ( $verbose === true ){
						echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
					}
					throw new \Exception('An error occurred while saving elements within the cache.', NULL, $ex);
				}
			}
			try{
				return $this->translateElements($text, $locale, $originalLocale);
			}catch(\Exception $ex){
				throw new \Exception($ex->getMessage());
			}
		}
		
		/**
		* Detects the language of a given text, then returns the language code.
		*
		* @param string|array $text A string containing the text that shall be analyzed.
		* @param array $hints An optional sequentiall array of strings containing the codes of the languages of which the text is expected to be, note that this option is supported by Yandex only.
		* @param bool $fresh If set to "true" all detections will be made by requesting data directly to the provider ignoring cache, otherwise, if the cached has been configured, data will be searched in cache before doing the requests.
		*
		* @return array An associative array of strings containing as key the original text and as value the detected langauge code or null if no language has been found.
		*
		* @throws InvalidArgumentException If an invalid text is given.
		* @throws InvalidArgumentException If the given text is over 10000 chars length, note that this exception can be thrown only when using Yandex as provider.
		* @throws InvalidArgumentException If an invalid locale code is given as target language.
		* @throws BadMethodCallException If no token has previously been defined.
		* @throws Exception If an error occurs during the HTTP request.
		* @throws Exception If an invalid response is received from the provider.
		* @throws Exception If the Yandex API key that has been set is not valid according to Yandex.
		* @throws Exception If the Yandex API key that has been set has been blocked by Yandex.
		* @throws Exception If daily limit of translatable chars of the Yandex API key has been reached.
		* @throws Exception If the request fails at Google side.
		* @throws Exception If an error occurrs while fetching data from the cache.
		* @throws Exception If an error occurrs while saving the data into the cache.
		*/
		public function detectLanguage($text, array $hints = NULL, bool $fresh = false): array{
			if ( $this->getToken() === '' ){
				throw new \BadMethodCallException('No token has been defined.');
			}
			if ( is_array($text) === false ){
				$text = array($text);
			}
			$provider = $this->getProvider();
			$verbose = $this->getVerbose();
			foreach ( $text as $key => $value ){
				if ( $value === NULL || $value === '' || is_string($value) === false ){
					unset($text[$key]);
					continue;
				}
				if ( $provider === self::YANDEX && mb_strlen($value, 'UTF-8') > 10000 ){
					throw new \InvalidArgumentException('The given text is too long.');
				}
			}
			$text = array_values(array_unique($text));
			if ( isset($text[0]) === false ){
				throw new \InvalidArgumentException('No valid text were found.');
			}
			$data = array();
			if ( $fresh !== true && $this->cacheReady() === true ){
				$keys = array();
				foreach ( $text as $key => $value ){
					$keys['PHPLocaleKit.detect:' . hash('md5', $value)] = $value;
				}
				try{
					$elements = $this->cacheHandler->pullMulti(array_keys($keys), true);
				}catch(\Exception $ex){
					if ( $verbose === true ){
						echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
					}
					throw new \Exception('An error occurred while fetching data from the cache.', NULL, $ex);
				}
				$text = array();
				foreach ( $elements as $key => $value ){
					if ( $value === NULL || is_string($value) === false ){
						$text[] = $keys[$key];
						continue;
					}
					$data[$keys[$key]] = $value;
				}
				if ( isset($text[0]) === false ){
					return $data;
				}
				try{
					$results = $this->detectElementsLanguage($text, ( $hints === NULL ? array() : $hints ));
				}catch(\Exception $ex){
					throw new \Exception($ex->getMessage());
				}
				$keys = array_flip($keys);
				$save = array();
				foreach ( $results as $key => $value ){
					$save[$keys[$key]] = $value;
					$data[$key] = $value;
				}
				try{
					$this->cacheHandler->pushMulti($save, true);
					return $data;
				}catch(\Exception $ex){
					if ( $verbose === true ){
						echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
					}
					throw new \Exception('An error occurred while saving elements within the cache.', NULL, $ex);
				}
			}
			try{
				return $this->detectElementsLanguage($text, ( $hints === NULL ? array() : $hints ));
			}catch(\Exception $ex){
				throw new \Exception($ex->getMessage());
			}
		}
		
		/**
		* Returns a list of all the languages supported by the current service provider.
		*
		* @param string $language A string containing an optional locale code in which the language names will be returned, by default languages are returned in English.
		*
		* @return array An associative array of strings containing as key the language code and as value the language name in the language that has been specified, by default in English.
		*
		* @throws BadMethodCallException If no token has previously been defined.
		* @throws Exception If an error occurs during the HTTP request.
		* @throws Exception If an invalid response is received from the provider.
		* @throws Exception If the Yandex API key that has been set is not valid according to Yandex.
		* @throws Exception If the Yandex API key that has been set has been blocked by Yandex.
		* @throws Exception If the request fails at Google side.
		*/
		public function getSupportedLanguages(string $language = 'en'): array{
			if ( $language === NULL || $language === '' ){
				$language = 'en';
			}
			$token = $this->getToken();
			if ( $token === '' ){
				throw new \BadMethodCallException('No token has been defined.');
			}
			$provider = $this->getProvider();
			$verbose = $this->getVerbose();
			try{
				switch ( $provider ){
					case self::GOOGLE:{
						//TODO: Add support for "model".
						$results = self::sendRequest('https://translation.googleapis.com/language/translate/v2/languages', array(
							'target' => $language === NULL || $language === '' ? 'en' : $language,
							'key' => $token
						));
					}break;
					default:{
						$results = self::sendRequest('https://translate.yandex.net/api/v1.5/tr.json/getLangs', array(
							'ui' => $language === NULL || $language === '' ? 'en' : $language,
							'key' => $token
						));
					}break;
				}
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('Unable to complete the request.', NULL, $ex);
			}
			if ( $results === '' ){
				throw new \Exception('Invalid response from the provider.');
			}
			$results = json_decode($results, true);
			if ( $results === NULL ){
				throw new \Exception('Invalid response from the provider.');
			}
			$languages = array();
			switch ( $provider ){
				case self::GOOGLE:{
					if ( isset($results['data']) === false || isset($results['data']['languages']) || is_array($results['data']['languages']) === false || isset($results['data']['languages'][0]) === false ){
						throw new \Exception('Invalid response from Google.');
					}
					foreach ( $results['data']['languages'] as $key => $value ){
						if ( isset($value['language']) === true && isset($value['name']) === true && $value['language'] !== '' && $value['name'] !== '' && is_string($value['name']) === true && is_string($value['language']) === true ){
							$languages[$value['language']] = $value['name'];
						}
					}
				}break;
				default:{
					if ( isset($results['code']) === true && $results['code'] !== 200 ){
						throw new \Exception(isset($results['code']) === false ? 'Invalid response from Yandex.' : self::getErrorMessageByErrorCode($results['code'], 1));
					}
					if ( isset($results['langs']) === false || is_array($results['langs']) === false ){
						throw new \Exception('Invalid response from Yandex.');
					}
					foreach ( $results['langs'] as $key => $value ){
						if ( $key !== '' && $value !== '' && is_string($key) === true && is_string($value) === true ){
							$languages[$key] = $value;
						}
					}
				}break;
			}
			return $languages;
		}
		
		/**
		* Checks if a given language is supported by the provider in use.
		*
		* @param String language A string containing the language code that shall be tested.
		*
		* @return Boolean If the given language is supported by the provider will be returned "true", otherwise "false".
		*
		* @throws InvalidArgumentException If an invalid language code were given.
		* @throws BadMethodCallException If no token has previously been defined.
		* @throws Exception If an error occurs while getting the supported languages from the provider.
		*/
		public function languageSupported(string $language): bool{
			if ( $language === NULL || $language === '' ){
				throw new \InvalidArgumentException('Invalid language code.');
			}
			if ( $this->getToken() === '' ){
				throw new \BadMethodCallException('No token has been defined.');
			}
			$verbose = $this->getVerbose();
			try{
				return in_array(strtolower($language), array_keys($this->getSupportedLanguages('en'))) === true ? true : false;
			}catch(\Exception $ex){
				if ( $verbose === true ){
					echo '[php-locale-kit] Exception (' . $ex->getCode() . '):' . $ex->getMessage() . PHP_EOL;
				}
				throw new \Exception('An error occurred while getting the supported languages from the provider.', NULL, $ex);
			}
		}
	}
}