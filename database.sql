/*
	CREATE TABLE FOR INFORMATION ABOUT LOCALES AND LANGUAGES.
	
	id: An integer number greater than zero representing the locale ID, this is useful when multiple locale codes refer to the same label set.
	code: A string representing the language code and the country code separated by "-", exapmple: en-US.
	lang: A string representing the language as of ISO 639-1, example: en, it, ru, jp.
	locked: If set to "true" the labels belonging to this locale cannot be translated used authomatic translation, then will be ignored.
*/
CREATE TABLE IF NOT EXISTS locales (id INTEGER NOT NULL, code TEXT NOT NULL, lang TEXT NOT NULL, locked BOOLEAN DEFAULT FALSE, PRIMARY KEY(id));

/*
	CREATE TABLE FOR LABELS (USING INTEGER IDENTIFIERS).
	
	id: An integer number greater than zero representing the label id.
	locale: An interger number greater than zero representing the locale ID.
	value: A string containing the label's text.
	locked: If set to "true" this label cannot be translated used authomatic translation, then will be ignored.
*/
CREATE TABLE IF NOT EXISTS labels (id INTEGER NOT NULL, locale INTEGER NOT NULL, value TEXT NOT NULL, locked BOOLEAN DEFAULT FALSE, PRIMARY KEY(id, locale));

/*
	CREATE TABLE FOR LABELS (USING TEXTUAL IDENTIFIERS).
*/
CREATE TABLE IF NOT EXISTS labels (id TEXT NOT NULL, locale INTEGER NOT NULL, value TEXT NOT NULL, locked BOOLEAN DEFAULT FALSE, PRIMARY KEY(id, locale));

/*
	THIS TABLE WILL CONTAIN SOME ADDITIONAL INFORMATION FOR THE PACKAGE (SUCH AS THE PACKAGE IDENTIFIER USED IN CACHING).
	
	key: A string containing the name of the entry.
	value: A string containing its value.
*/
CREATE TABLE IF NOT EXISTS meta (key TEXT NOT NULL, value TEXT, PRIMARY KEY(key));