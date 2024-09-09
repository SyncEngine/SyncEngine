import React, { cloneElement, useRef } from 'react';
import { Collapse } from 'react-bootstrap';
import { createRefId } from '../../../utils/globals';
import useRootClose from '../../../hooks/useRootClose';
import useToggle from '../../../hooks/useToggle';

export default function Collapsible( props ) {

	const {
		collapsed = true,
		dimension = 'height',
		openCallback,
		closeCallback,
		trigger,
	} = props;

	const [ opened, handleToggle, handleOpen, handleClose ] = useToggle( ! collapsed, openCallback, closeCallback );

	const root = useRootClose( handleClose );
	const ref = useRef( createRefId() );

	const triggerProps = {
		onClick: handleToggle,
		onChange: handleToggle,
		'aria-controls': ref.current,
		'aria-expanded': opened,
	}

	return (
		<>
			{ typeof trigger === 'function' ? trigger( triggerProps, opened ) : cloneElement( trigger, triggerProps ) }
			<Collapse in={ opened } dimension={ dimension }>
				<div ref={ root } id={ ref.current } className={ props.className }>{ props.children }</div>
			</Collapse>
		</>
	);
}
