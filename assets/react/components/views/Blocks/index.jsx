import React from 'react';
import PropTypes from 'prop-types';
import Ref from './Ref';
import Info from './Info';
import Link from './Link';
import Actions from './Actions';
import Config from './Config';
import Entities from './Entities';
import { isBool, isEmpty, isObject, isTrue, validate } from '../../../utils/conditions';
import { parseTagString } from '../../../utils/tags';

export default function Block( props ) {
	const {
		block,
		args,
		content,
	} = props;

	if ( 'function' === typeof block ) {
		return block( { ...props, ...args } );
	}

	if ( 'condition' === block ) {
		if ( ! isEmpty( args.options ) ) {
			const options = args.options;
			const item = args.item ?? props.item;
			for ( const option in options ) {
				if ( ! options[ option ] ) {
					continue;
				}
				const optionData = options[ option ];
				const value = parseTagString( optionData.value, item );

				if (
					( isObject( optionData.compare ) && validate( optionData.compare, value ) )
					||
					( isBool( optionData.compare ) && optionData.compare === isTrue( value ) )
					||
					( optionData.compare === value )
				) {
					return <Block { ...optionData } item={ item } value={ value } />;
				}
			}
		}
	}

	switch ( block ) {
		case 'ref':
		case 'id':
			return <Ref { ...props } { ...args } key={ props.key ?? args?.key ?? null } />
		case 'info':
			return <Info { ...props } { ...args } key={ props.key ?? args?.key ?? null } />
		case 'link':
			return <Link { ...props } { ...args } key={ props.key ?? args?.key ?? null } />
		case 'actions':
			return <Actions { ...props } { ...args } key={ props.key ?? args?.key ?? null } />
		case 'config':
			return <Config { ...props } { ...args } key={ props.key ?? args?.key ?? null } />
		case 'entities':
			return <Entities { ...props } { ...args } key={ props.key ?? args?.key ?? null } />
	}

	if ( 'object' === typeof content ) {
		return <>{ JSON.stringify( content ) }</>
	}

	// Maybe parse object as JSON?
	return <>{ content }</>
}

Block.propTypes = {
	block: PropTypes.oneOfType( [ PropTypes.string, PropTypes.func ] ),
	args: PropTypes.object,
	content: PropTypes.any,
	item: PropTypes.object,
};
