import React, { useCallback } from 'react';
import { FloatingLabel, Form, InputGroup } from 'react-bootstrap';

import Help from '../../form/Help';
import Description from '../../form/Description';

import { objectToMappable } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';

export default function Select( props ) {
	const {
		label,
		attr,
		id = attr.id ?? createRefId(),
		onChange,
	} = props;

	const handleChange = useCallback( ( e ) => {
		onChange( e.target.value );
	}, [ onChange, id, props.name ] );

	// @todo deprecate options support?
	let choices = objectToMappable( props.choices ?? props.options ?? {}, 'value', 'label' );

	return (
		<div className="flex-grow-1">
			<InputGroup>
				{ props.help &&
					<Help id={ id } text={ props.help } inputGroup={ true } />
				}
				<FloatingLabel label={ label }>
					<Form.Select
						{ ...attr }
						label={ label }
						placeholder={ props.placeholder ?? props.label }
						value={ props.value ?? props.default ?? '' }
						onChange={ handleChange }
					>
						{ ! isEmpty( choices[0].value ) &&
						  <option value="">{ props.selectLabel ?? '-- Select --' }</option>
						}
						{
							choices.map( ( option, index ) => {
								return <option key={ index } value={ option.value }>{ option.label ?? option.value }</option>
							} )
						}
					</Form.Select>
				</FloatingLabel>
			</InputGroup>
			{ props.description &&
				<Description text={ props.description } id={ id } />
			}
		</div>
	);
}
