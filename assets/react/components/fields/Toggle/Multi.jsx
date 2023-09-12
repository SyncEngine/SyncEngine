import React, { useCallback } from 'react';
import { Form } from 'react-bootstrap';
import { isEmpty } from '../../../utils/conditionals';
import { objectToMappable } from '../../../utils/data';
import { createRefId } from '../../../utils/globals';
import Description from '../../form/Description';

export default function ToggleMulti( props ) {

	const {
		attr,
		id = attr.id ?? createRefId(),
		label,
		help,
		type,
		onChange,
	} = props;

	const description = props.description && <Description text={ props.description } id={ id } />;

	const handleCheck = useCallback( ( e ) => {
		let value = props.value;
		if ( ! value || 'object' !== typeof value ) {
			value = [];
		}
		if ( e.target.checked ) {
			value.push( e.target.value );
		} else {
			let index = value.indexOf( e.target.value );
			if ( -1 !== index ) {
				value.splice( index, 1 );
			}
		}
		onChange( value );
	}, [ onChange, id, props.name, props.value ] );

	const isChecked = useCallback( ( value, props ) => {
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
	}, [] );

	return (
		<div>
			<div className="mb-1"><span>{ label }</span>{ help }</div>
			{ description }
			{
				objectToMappable( props.choices ?? {}, 'value', 'label' ).map( ( option, index ) => {
					return <Form.Check
						id={ attr.id + option.value }
						key={ option.value }
						value={ option.value }
						onChange={ handleCheck }
						label={ option.label }
						checked={ isChecked( option.value, props ) }
						type={ ( 'switch' === type || 'toggle' === type ) ? 'switch' : 'checkbox' }
						inline={ ! isEmpty( props.inline ) }
					/>;
				} )
			}
		</div>
	);
}
