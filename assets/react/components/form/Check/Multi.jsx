import React from 'react';
import { ButtonGroup, Form, ToggleButton } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditions';
import { getCheckButtonVariant } from './';
import Label from '../Label';

export function isChecked( value, props ) {
	if ( ! isEmpty( value ) ) {
		if ( props.value ) {
			if ( Array.isArray( props.value ) ) {
				return props.value.includes( value );
			}
			return props.value === value;
		}
		if ( props.default ) {
			if ( Array.isArray( props.default ) ) {
				return props.default.includes( value );
			}
			return props.default === value;
		}
	}
	return false;
}

export default function CheckMulti( props ) {

	if ( props.button ) {
		const variant = getCheckButtonVariant( props );
		return (
			<ButtonGroup
				vertical={ props.vertical ?? isEmpty( props.inline ) } // Opt-out for vertical.
			>
				{
					objectToMappable( props.choices ?? {}, 'value', 'label' ).map( choice => {
						return (
							<ToggleButton
								id={ props.id + choice.value }
								key={ choice.value }
								value={ choice.value }
								onChange={ props.onChange }
								checked={ isChecked( choice.value, props ) }
								type={ 'radio' === props.type ? 'radio' : 'checkbox' }
								variant={ choice.variant ?? variant }
								disabled={ props.disabled }
								readOnly={ props.readonly ?? props.readOnly }
							>
								<Label>{ choice }</Label>
							</ToggleButton>
						)
					} )
				}
			</ButtonGroup>
		)
	}

	return (
		<>
			{
				objectToMappable( props.choices ?? {}, 'value', 'label' ).map( choice => {
					return <Form.Check
						id={ props.id + choice.value }
						key={ choice.value }
						value={ choice.value }
						onChange={ props.onChange }
						label={ <Label>{ choice }</Label> }
						checked={ isChecked( choice.value, props ) }
						type={ props.type }
						inline={ ! isEmpty( props.inline ) }
						disabled={ props.disabled }
						readOnly={ props.readonly ?? props.readOnly }
					/>;
				} )
			}
		</>
	);
}
