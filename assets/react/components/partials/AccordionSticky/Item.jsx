import React, { forwardRef, useContext } from 'react';
import { AccordionContext, AccordionItem } from 'react-bootstrap';

export default forwardRef( function AccordionItemSticky( props, ref ) {

	const { activeEventKey } = useContext( AccordionContext ) ?? [];
	console.log( activeEventKey );

	const isCurrentEventKey = activeEventKey && ( Array.isArray( activeEventKey ) ? activeEventKey.includes( props.eventKey ) : activeEventKey === props.eventKey );

	let className = props.className ?? '';
	if ( isCurrentEventKey ) {
		className = ( className ) ? ' sticky' : 'sticky';
	}

	return <AccordionItem { ...props } ref={ ref } className={ className } />
} );
