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

function toFormat( data, format ) {
	if ( isEmpty( data ) ) {
		return '';
	}

	switch ( format ) {

		case 'json':
			return JSON.stringify( data );

		case 'yml':
		case 'yaml':
			return YAML.stringify( data );

		case 'url':
			return QS.stringify( data );

	}
	return data;
}

function fromFormat( string, format ) {
	if ( isEmpty( string ) ) {
		return {};
	}

	switch ( format ) {

		case 'json':
			return JSON.parse( string );

		case 'yml':
		case 'yaml':
			return YAML.parse( string );

		case 'url':
			return QS.parse( string );

	}
	return string;
}

export {
	getFormats,
	fromFormat,
	toFormat
}
