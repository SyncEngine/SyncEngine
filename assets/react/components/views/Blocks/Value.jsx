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

	const dateFormatter = useDateFormatter();
	//const [ models, callbacks ] = useModels( type );

	let value = props.value ?? item[ prop ] ?? item[ fallback ];

	parse.split( '|' ).map( name => {
		switch ( name ) {
			case 'date':
				value = dateFormatter.format( value * 1000 );
				break;
			case 'tag':
				value = parseTagString( value, item );
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

	return '...';
}
