import React from 'react';
import Ref from './Ref';
import Info from './Info';
import Actions from './Actions';
import Config from './Config';
import Entities from './Entities';

export default function Block( props ) {
	const {
		block,
		args,
		content,
	} = props;

	switch ( block ) {
		case 'ref':
		case 'id':
			return <Ref { ...args } { ...props } />
		case 'info':
			return <Info { ...args } { ...props } />
		case 'actions':
			return <Actions { ...args } { ...props } />
		case 'config':
			return <Config { ...args } { ...props } type={ args.type } />
		case 'entities':
			return <Entities { ...args } { ...props } type={ args.type } />
	}

	if ( 'object' === typeof content ) {
		return <>{ JSON.stringify( content ) }</>
	}

	// Maybe parse object as JSON?
	return <>{ content }</>
}
