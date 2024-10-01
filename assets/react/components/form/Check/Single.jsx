import React from 'react';
import { Form, ToggleButton } from 'react-bootstrap';
import { getCheckButtonVariant } from './';
import Label from '../Label';
import { HStack, VStack } from '../../partials/Stack';
import { isValue } from '../../../utils/conditions';

export default function CheckSingle( props ) {

	if ( props.button ) {
		return (
			<ToggleButton { ...props } toggle={ null } type={ 'checkbox' } label={ null } button={ null } variant={ getCheckButtonVariant( props ) }>
				<Label>{ props }</Label>
			</ToggleButton>
		)
	}

	// @todo better option to set on/off labels?
	if ( props.toggle ) {

		const off = (
			<span
				className={ ( props.checked ? 'opacity-50' : '' ) }
				onClick={ ( e ) => {
					e.target.checked = ! props.checked;
					props.onChange( e );
				} }
			>
				<Label>{ props.toggle[0] }</Label>
			</span>
		);

		const on = (
			<Form.Check
				{ ...props }
				toggle={ null }
				label={ isValue( props.toggle[1] ) ? <Label>{ props.toggle[1] }</Label> : props.label }
				inline={ true }
				className={ props.className + ' m-0' + ( ! props.checked ? ' opacity-50' : '' ) }
			/>
		);

		const toggle = (
			<HStack gap={ 2 } className="mb-1">
				{ off }{ on }
			</HStack>
		)

		if ( props.toggle[1] && props.label ) {
			return (
				<VStack gap={2}>
					{ props.label }
					{ toggle }
				</VStack>
			)
		}

		return toggle;
	}

	return (
		<Form.Check { ...props } toggle={ null } />
	);
}
