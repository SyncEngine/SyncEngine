import YAML from 'yaml';
import { isEmpty } from './conditionals';

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
			return Object.entries( data ).map(([key, val]) => `${key}=${encodeURIComponent(val)}`).join('&');

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
			return JSON.parse('{"' + string.replace(/&/g, '","').replace(/=/g,'":"') + '"}', function(key, value) { return key===""?value:decodeURIComponent(value) });

	}
	return string;
}

export {
	getFormats,
	fromFormat,
	toFormat
}
