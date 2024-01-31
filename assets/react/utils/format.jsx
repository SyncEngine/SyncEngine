import YAML from 'yaml';
import { isEmpty } from './conditions';

const QS = require( 'qs' );

function getFormats( enabledFormats ) {
	return {
		json: 'JSON',
		yaml: 'YAML',
		url: 'URL',
	};
}

function toFormat( data, format, options ) {
	if ( isEmpty( data ) ) {
		return '';
	}

	switch ( format ) {

		case 'json':
			return JSON.stringify( data, ...( options ?? [] ) );

		case 'yml':
		case 'yaml':
			return YAML.stringify( data, options );

		case 'url':
			return QS.stringify( data, options );

	}
	return data;
}

function fromFormat( string, format, options ) {
	if ( isEmpty( string ) ) {
		return {};
	}

	switch ( format ) {

		case 'json':
			return JSON.parse( string, options );

		case 'yml':
		case 'yaml':
			return YAML.parse( string, options );

		case 'url':
			return QS.parse( string, options );

	}
	return string;
}

/**
 * This function is same as PHP's nl2br() with default parameters.
 *
 * @param {string} str Input text
 * @param {boolean} replaceMode Use replace instead of insert
 * @param {boolean} isXhtml Use XHTML
 * @return {string} Filtered text
 */
function nl2br (str, replaceMode, isXhtml) {

	var breakTag = (isXhtml) ? '<br />' : '<br>';
	var replaceStr = (replaceMode) ? '$1'+ breakTag : '$1'+ breakTag +'$2';
	return (str + '').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g, replaceStr);
}

/**
 * This function inverses text from PHP's nl2br() with default parameters.
 *
 * @param {string} str Input text
 * @param {boolean} replaceMode Use replace instead of insert
 * @return {string} Filtered text
 */
function br2nl (str, replaceMode) {

	var replaceStr = (replaceMode) ? "\n" : '';
	// Includes <br>, <BR>, <br />, </br>
	return str.replace(/<\s*\/?br\s*[\/]?>/gi, replaceStr);
}

export {
	getFormats,
	fromFormat,
	toFormat,
	nl2br,
	br2nl,
}
