import YAML from 'yaml';
import { isEmpty } from './conditionals';

function getFormats( enabledFormats ) {
	let formats = {
		json: 'JSON',
		csv: 'CSV',
		yaml: 'YAML',
	};

	if ( Array.isArray( enabledFormats ) ) {
		for ( const key in formats ) {
			if ( ! enabledFormats.includes( key ) ) {
				delete formats[ key ];
			}
		}
	}

	return formats;
}

function toFormat( data, format ) {
	if ( isEmpty( data ) ) {
		return '';
	}

	switch ( format ) {

		case 'json':
			return JSON.stringify( data );

		case 'csv':
			return stringifyCSV( data );

		case 'yml':
		case 'yaml':
			return YAML.stringify( data );

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

		case 'csv':
			return parseCsv( string );

		case 'yml':
		case 'yaml':
			return YAML.parse( string );

	}
	return string;
}

function stringifyCSV( arr ) {
	if ( 'object' !== typeof arr ) {
		return String( arr );
	}

	if ( ! Array.isArray( arr ) ) {
		arr = [Object.keys(arr[0])].concat(arr)
	}

	return arr.map(it => {
		return Object.values(it).toString()
	}).join('\n')
}

function parseCsv( string, config = {} ) {
	if ( 'object' === typeof string ) {
		return string;
	}
	string = String( string );

	config = {
		delimiter: ',',
		...config
	}

	const titles = string.slice(0, string.replace(/(\r)/gm, "").indexOf('\n')).split( config.delimiter );
	return string
		.slice(string.indexOf('\n') + 1)
		.split('\n')
		.map(v => {
			const values = v.split( config.delimiter );
			return titles.reduce(
				(obj, title, index) => ((obj[title] = values[index].trim()), obj),
				{}
			);
		});
}

export {
	getFormats,
	fromFormat,
	toFormat
}
