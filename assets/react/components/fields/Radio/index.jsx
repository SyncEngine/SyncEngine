import React, { useCallback } from 'react';
import Description from '../../form/Description';
import { objectToMappable } from '../../../utils/data';
import { Form } from 'react-bootstrap';
import { isEmpty } from '../../../utils/conditionals';
import { createRefId } from '../../../utils/globals';
import Help from '../../form/Help';

export default function Radio( props ) {
	const {
		label,
		attr,
		id = attr.id ?? createRefId(),
		onChange,
	} = props;

	const handleChange = useCallback( ( e ) => {
		onChange( e.target.value );
	}, [ onChange, id, props.name ] );

	return (
		<div>
			<div className="mt-n1 mb-1"><span>{ label }</span>{ props.help && <Help text={ props.help } id={ id } /> }</div>
			{ props.description && <Description text={ props.description } id={ id } /> }
			{
				objectToMappable( props.choices ?? {}, 'value', 'label' ).map( ( option, index ) => {
					return <Form.Check
						id={ id + option.value }
						key={ option.value }
						value={ option.value }
						onChange={ handleChange }
						label={ option.label }
						checked={ props.value ? props.value === option.value : props.default === option.value }
						type={ 'radio' }
						inline={ ! isEmpty( props.inline ) }
					/>;
				} )
			}
		</div>
	)
}
