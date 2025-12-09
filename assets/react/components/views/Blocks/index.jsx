import React from 'react';
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
			return <Ref { ...props } { ...args } />
		case 'info':
			return <Info { ...props } { ...args } />
		case 'link':
			return <Link { ...props } { ...args } />
		case 'actions':
			return <Actions { ...props } { ...args } />
		case 'config':
			return <Config { ...props } { ...args } />
		case 'entities':
			return <Entities { ...props } { ...args } />
	}

	if ( 'object' === typeof content ) {
		return <>{ JSON.stringify( content ) }</>
	}

	// Maybe parse object as JSON?
	return <>{ content }</>
}
