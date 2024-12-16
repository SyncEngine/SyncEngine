import React, { useCallback } from 'react';
import { createRefId } from '../../../utils/globals';
import Description from '../../form/Description';
import Help from '../../form/Help';
import { CheckMulti } from '../../form/Check';
import { isFieldEditable } from '../../../utils/conditions';
import useFieldChoices from '../../../hooks/useFieldChoices';
import { useTranslation } from 'react-i18next';

export default function ToggleMulti( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );
	const [ choices, setChoices, loading ] = useFieldChoices( props );

	const {
		attr = {},
		id = attr.id ?? createRefId(),
		type,
		onChange,
	} = props;

	const label = props.label && <div className="mb-1"><span>{ props.label }</span>{ props.help && <Help text={ props.help } id={ id }/> }</div>
	const description = props.description && <Description text={ props.description } id={ id }/>;

	const handleCheck = useCallback( ( e ) => {
		if ( ! editable ) return;
		// Remove reference to trigger change.
		let value = props.value ? [ ...props.value ] : [];
		if ( ! value || 'object' !== typeof value ) {
			value = [];
		}
		if ( e.target.checked ) {
			if ( ! value.includes( e.target.value ) ) {
				value.push( e.target.value );
			}
		} else {
			let index = value.indexOf( e.target.value );
			if ( -1 !== index ) {
				value.splice( index, 1 );
			}
		}
		onChange( value );
	}, [ onChange, id, props.name, props.value, editable ] );

	return (
		<div>
			{ label }
			{ description }
			<CheckMulti
				id={ id }
				onChange={ handleCheck }
				choices={ choices ?? ( loading ? [ t( 'Loading ...' ) ] : [] ) }
				value={ props.value }
				default={ props.default }
				button={ props.buttons ?? props.button }
				inline={ props.inline }
				vertical={ props.vertical }
				required={ props.required ?? attr.required }
				disabled={ props.disabled ?? attr.disabled }
				readOnly={ ! editable || ( props.readOnly ?? props.readonly ?? attr.readOnly ?? attr.readonly ) }
				type={ ( 'switch' === type || 'toggle' === type ) ? 'switch' : 'checkbox' }
			/>
		</div>
	);
}
