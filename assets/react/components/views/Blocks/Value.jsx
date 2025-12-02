import React, { useState } from 'react';
import { parseTagString } from '../../../utils/tags';
import useDateFormatter from '../../../hooks/useDateFormatter';
import useModels from '../../../hooks/useModels';
import { isString } from '../../../utils/conditions';
import Icon from '../../partials/Icon';
import { HStack } from '../../partials/Stack';
import { suppress } from '../../../utils/events';
import { round } from '../../../utils/globals';
import { objectToMappable } from '../../../utils/data';

export default function Value( props ) {
	const {
		item = {},
		icon,
		type = item.type,
		prop = 'label',
		fallback = 'name',
		parse = '',
		prefix = '',
		postfix = '',
	} = props;

	let value = props.value ?? item[ prop ] ?? item[ fallback ];

	if ( parse ) {
		let parsers = isString( parse ) ? parse.split( '|' ) : objectToMappable( parse, 'type', 'args' );

		parsers.map( parser => {
			let args = {};
			if ( ! isString( parser ) ) {
				args   = parser.args || {};
				parser = parser.type;
			}
			switch ( parser ) {
				case 'tag':
					value = parseTagString( value, item );
					break;
				case 'date':
					value = <DateValue value={ value } { ...args } />;
					break;
				case 'model':
					value = <ModelValue value={ value } type={ type + 's' } prop={ 'name' } { ...args } />;
					break;
			}
		} );
	}

	if ( value ) {
		return (
			<HStack>
				{ icon && <Icon icon={ icon } className="me-2 d-flex align-items-center" /> }
				{ isString( prefix ) ? prefix : <Value { ...prefix }/> }
				{ value }
				{ isString( postfix ) ? postfix : <Value { ...postfix }/> }
			</HStack>
		)
	}

	if ( props.default ) {
		return (
			<HStack>
				{ icon && <Icon icon={ icon } className="me-2 d-flex align-items-center" /> }
				{ props.default }
			</HStack>
		)
	}

	return icon ? <Icon icon={ icon } className="me-2 d-flex align-items-center" /> : '';
}

export function DateValue( { value, ms = false, options = null } ) {
	if ( isNaN( value ) ) {
		value = Date.parse( value );
		ms    = true;
	}

	const dateFormatter = useDateFormatter( options );

	return dateFormatter.format( ms ? value : value * 1000 )
}

export function DurationValue( { value, ms = false, initialView = '' } ) {
	const [ view, setView ] = useState( initialView );

	const hasMinutes = 'm' === initialView || ms ? value > 60000 : value > 60;

	const switchView = (e) => {
		suppress(e);
		switch ( view ) {
			case '':
				setView( 'ms' );
				break;
			case 'ms':
				setView( 's' );
				break;
			case 's':
				setView( hasMinutes ? 'm' : '' );
				break;
			default:
				setView( '' );
				break;
		}
	}

	let parsed;
	switch ( view ) {
		case 's':
			parsed = round( ms ? value / 1000 : value, 3 );
			break;
		case 'm':
			parsed = round( ms ? value / 60000 : value / 60, 2 );
			break;
		case 'ms':
			parsed = ms ? value : value * 1000;
			break;
		default:
			let total_seconds = ms ? value / 1000 : value;
			const hours   = Math.floor(total_seconds / 3600);
			const minutes = Math.floor(total_seconds / 60) % 60;
			const seconds_ms = ( total_seconds % 60 ).toFixed(3);

			parsed = [ hours, minutes, seconds_ms ].map(v => v<10?'0'+v:v).join(':');
	}

	return <span onClick={ switchView }>{ parsed }{ view }</span>
}

export function MemoryValue( { value, initialView = '' } ) {
	const [ view, setView ] = useState( initialView );

	const hasMb = 'M' === initialView || value > ( 1024 * ( 1024 / 10 ) );

	const switchView = (e) => {
		suppress(e);
		switch ( view ) {
			case '':
				setView( 'K' );
				break;
			case 'K':
				setView( hasMb ? 'M' : '' );
				break;
			default:
				setView( '' );
				break;
		}
	}

	let parsed;
	switch ( view ) {
		case 'K':
			parsed = round( value / 1024, 2 );
			break;
		case 'M':
			parsed = round( ( value / 1024 ) / 1024, 2 );
			break;
		default:
			parsed = value;
	}

	return <span onClick={ switchView }>{ parsed }{ view ?? 'B' }</span>
}

export function ModelValue( props ) {
	const {
		value, type, prop
	} = props;

	const [ models, callbacks, loading ] = useModels( type, {}, {} );

	if ( ! loading ) {
		const model = models[ value ];

		if ( model ) {
			return model[ prop ] ?? value;
		}
	}

	return value ?? '...';
}
