import React from 'react';
import Ref from './Ref';
import Info from './Info';
import Link from './Link';
import Actions from './Actions';
import Config from './Config';
import Entities from './Entities';

export default function Block( props ) {
	const {
		block,
		args,
		content,
	} = props;

	if ( 'function' === typeof block ) {
		return block( { ...props, ...args } );
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
