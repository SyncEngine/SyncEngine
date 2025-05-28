import React, { useContext, useRef } from 'react';
import { Overlay, Popover } from 'react-bootstrap';

import useToggle from '../../../hooks/useToggle';
import useRootClose from '../../../hooks/useRootClose';
import { ParentContext } from '../../../context/ParentContext';
import { ContainerContext } from '../../../context/ContainerContext';

function getTriggerProps( triggers, callback, enable, disable ) {
	let hover = false;
	let blur = false;
	const props = triggers.map( prop => {
		let action = callback;
		switch ( prop ) {
			case 'click':
				prop = 'onClick';
				break;
			case 'change':
				prop = 'onChange';
				break;
			case 'hover':
			case 'onHover':
				prop = 'onMouseOver';
				action = enable;
				hover = true;
				break;
			case 'focus':
				prop = 'onFocus';
				action = enable;
				blur = true;
				break;
			case 'blur':
			case 'onBlur':
				prop = 'onBlur';
				action = enable;
				break;
		}
		return [ prop, action ];
	} );

	if ( hover ) {
		props.push( [ 'onMouseLeave', disable ] );
	}
	if ( blur ) {
		props.push( [ 'onBlur', disable ] );
	}

	return Object.fromEntries( props )
}

export default function OverlayToggle( props ) {
	const {
		trigger = 'onClick',
		overlay,
		children,
		onShow,
		onHide,
		placement = 'top',
		raw,
		prewrap,
		popperConfig,
		flip = true,
	} = props;

	const triggers = Array.isArray( trigger ) ? trigger : [ trigger ];

	const [ show, toggleShow, enableShow, disableShow ] = useToggle( false, onShow, onHide );
	const target = useRef( null );
	const rootClose = useRootClose( toggleShow );
	const container = props.container ?? useContext( ContainerContext ) ?? useContext( ParentContext ).container ?? target.current;

	const getContent = useRef( null );
	if ( ! getContent.current ) {
		getContent.current = ( content, raw, prewrap, triggerProps ) => {
			if ( raw ) {
				if ( React.isValidElement( content ) ) {
					return React.cloneElement( content, triggerProps );
				}
				content = <span dangerouslySetInnerHTML={ { __html: content } } />
			}
			if ( prewrap ) {
				content = <span style={ { whiteSpace: 'pre-wrap' } }>{ content }</span>
			}
			return (
				<Popover { ...triggerProps }><Popover.Body>{ content }</Popover.Body></Popover>
			);
		}
	}

	return (
		<>
			{ React.cloneElement( children, { ...getTriggerProps( triggers, toggleShow, enableShow, disableShow ), ref: target } ) }
			<Overlay
				ref={ rootClose }
				show={ show }
				target={ target.current }
				container={ container } // Required for input focus.
				placement={ placement }
				flip={ flip }
				popperConfig={ popperConfig }
				//rootClose={ true }
				//onHide={ toggleShow }
			>
				{ getContent.current( overlay, raw, prewrap, ( triggers.includes( 'hover' ) || triggers.includes( 'onHover' ) ) ? getTriggerProps( [ 'onHover' ], toggleShow, enableShow, disableShow ) : {} ) }
			</Overlay>
		</>
	);
}
