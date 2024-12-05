import React, { useState } from 'react';
import { parseTagString } from '../../../utils/tags';
import useDateFormatter from '../../../hooks/useDateFormatter';
import useModels from '../../../hooks/useModels';
import { isString } from '../../../utils/conditions';
import Icon from '../../partials/Icon';
import { HStack } from '../../partials/Stack';
import { suppress } from '../../../utils/events';
import { round } from '../../../utils/globals';

export default function Value( props ) {
	const {
		item = {},
		icon,
		type = item.type,
		prop = 'label',
		fallback = 'name',
		parse = 'item',
		prefix = '',
		postfix = '',
	} = props;

	let value = props.value ?? item[ prop ] ?? item[ fallback ];

	parse.split( '|' ).map( name => {
		switch ( name ) {
			case 'tag':
				value = parseTagString( value, item );
				break;
			case 'date':
				value = <DateValue value={ value } />;
				break;
			case 'model':
				value = <ModelValue value={ value } type={ type + 's' } prop={ 'name' } />;
				break;
		}
	} );

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

export function DateValue( { value, ms = false } ) {
	const dateFormatter = useDateFormatter();

	return dateFormatter.format( ms ? value : value * 1000 )
}

export function DurationValue( { value, ms = false, initialView = 'ms' } ) {
	const [ view, setView ] = useState( initialView );

	const hasMinutes = 'm' === initialView || ms ? value > 60000 : value > 60;

	const switchView = (e) => {
		suppress(e);
		switch ( view ) {
			case 'ms':
				setView( 's' );
				break;
			case 's':
				setView( hasMinutes ? 'm' : 'ms' );
				break;
			default:
				setView( 'ms' );
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
		default:
			parsed = ms ? value : value * 1000;
	}

	return <span onClick={ switchView }>{ parsed }{ view }</span>
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
