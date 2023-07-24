
import React from "react";
import styled from "styled-components";
import { components } from "react-select";

const Label = styled.label`
	height: 100%;
	display: flex;
	align-items: center;
	left: 1rem;
	margin-left: calc( var(--bs-border-width) * -1 );
	pointer-events: none;
	position: absolute;
	transform-origin: 0 0;
	transition: 0.2s ease all;
	-moz-transition: 0.2s ease all;
	-webkit-transition: 0.2s ease all;
	z-index: 4;

	transform: ${(props) => (props.$isFloating ? 'scale(0.85) translateY(-.4rem)' : '')};
	color: ${(props) => (props.$isFloating ? 'rgba(var(--bs-body-color-rgb), 0.65)' : '')};
`;

export const FloatingLabel = ({ children, ...props }) => {

	const {
		label,
		placeholder,
	} = props.selectProps;

	const styles = {
		padding: 0,
	}
	const isFloating = props.isFocused || props.hasValue || placeholder;

	if ( label ) {
		styles.paddingTop = '.875rem';
		styles.marginBottom = '-.125rem';
	}

	props.selectProps.styles.valueContainer = base => ({
		...base,
		...styles,
	});

	return (
		<>
			{ label &&
				<Label $isFloating={ isFloating }>{ label }</Label>
			}
			<components.Control { ...props } className="form-control" styles={ styles }>
				{ children }
			</components.Control>
		</>
	);
};
