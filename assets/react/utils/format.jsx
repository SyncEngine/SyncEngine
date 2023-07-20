import YAML from 'yaml';
import { isEmpty } from './conditionals';
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
			return JSON.stringify( data, ...options );

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

export {
	getFormats,
	fromFormat,
	toFormat
}
