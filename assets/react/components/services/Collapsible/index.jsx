import React, { cloneElement, useCallback, useRef, useState } from 'react';
import { Collapse } from 'react-bootstrap';
import { createRefId } from '../../../utils/globals';

export default function Collapsible( props ) {

	const {
		collapsed = true,
		dimension = 'height',
		trigger,
	} = props;

	const ref = useRef( createRefId() );

	const [ open, setOpen ] = useState( ! collapsed );

	const handleToggle = useCallback( () => {
		setOpen( ! open );
	}, [ open ] );

	const handleClick = useCallback( ( e ) => {
		if ( e ) {
			e.preventDefault();
			e.stopPropagation();
		}
		handleToggle();
	}, [ open ] );

	const triggerProps = {
		onClick: handleClick,
		onChange: handleToggle,
		'aria-controls': ref.current,
		'aria-expanded': open,
	}

	const collapsible = <Collapse in={ open } dimension={ dimension }><div id={ ref.current }>{ props.children }</div></Collapse>;

	return (
		<>
			{ typeof trigger === 'function' ? trigger( triggerProps, open ) : cloneElement( trigger, triggerProps ) }
			{ collapsible }
		</>
	);
}
