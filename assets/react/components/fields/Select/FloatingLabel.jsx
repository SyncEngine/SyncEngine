import React from 'react';
import { components } from 'react-select';
import Label from '../../form/Label';

export const FloatingLabel = ({ children, ...props }) => {

	const {
		label,
		placeholder,
	} = props.selectProps;

	const styles = {
		padding: 0,
	}
	const isFloating = ( props.isFocused || props.hasValue || placeholder );

	if ( label ) {
		styles.paddingTop = '.875rem';
		styles.marginBottom = '-.125rem';
	}

	props.selectProps.styles.valueContainer = base => ({
		...base,
		...styles,
	});

	let className = 'form-control';

	if ( false === props.selectProps.isEditable ) {
		className += ' bg-read-only';

		if ( ! props.innerProps ) {
			props.innerProps = {};
		}

		props.innerProps.readOnly = true;
	}

	const style = {
		'height': '100%',
		'display': 'flex',
		'alignItems': 'center',
		'left': '1rem',
		'marginLeft': 'calc( var(--bs-border-width) * -1 )',
		'pointerEvents': 'none',
		'position': 'absolute',
		'transformOrigin': '0 0',
		'transition': '0.2s ease all',
		//'-moz-transition': '0.2s ease all',
		//'-webkit-transition': '0.2s ease all',
		'zIndex': 'var(--z-index-floating-label)',
	}

	style.transform = isFloating ? 'scale(0.85) translateY(-.4rem)' : '';
	style.color = isFloating ? 'rgba(var(--bs-body-color-rgb), 0.65)' : '';

	return (
		<>
			{ label &&
				<label style={ { ...style } }><Label>{ label }</Label></label>
			}
			<components.Control { ...props } className={ className } styles={ styles }>
				{ children }
			</components.Control>
		</>
	);
};
