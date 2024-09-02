import React from 'react';
import { parseTagString } from '../../../utils/tags';
import useDateFormatter from '../../../hooks/useDateFormatter';
import useModels from '../../../hooks/useModels';

export default function Value( props ) {
	const {
		item = {},
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
			<>{ prefix }{ value }{ postfix }</>
		)
	}

	return props.default;
}

function DateValue( { value } ) {
	const dateFormatter = useDateFormatter();

	return dateFormatter.format( value * 1000 )
}

function ModelValue( props ) {
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
