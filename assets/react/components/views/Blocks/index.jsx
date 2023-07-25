import React from 'react';
import Ref from './Ref';
import Info from './Info';
import Actions from './Actions';

export default function Block( props ) {
	const {
		block,
		content,
	} = props;

	switch ( block ) {
		case 'ref':
		case 'id':
			return <Ref { ...props } />
		case 'info':
			return <Info { ...props } />
		case 'actions':
			return <Actions { ...props } />
	}

	// Maybe parse object as JSON?
	return <>{ content }</>
}
