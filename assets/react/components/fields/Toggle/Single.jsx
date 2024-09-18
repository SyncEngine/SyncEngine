import React, { useCallback } from 'react';
import { isEmpty, isFieldEditable } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';
import Description from '../../form/Description';
import Help from '../../form/Help';
import { CheckSingle } from '../../form/Check';
import Label from '../../form/Label';

export default function ToggleSingle( props ) {
	const editable = isFieldEditable( props );

	const {
		attr = {},
		id = attr.id ?? createRefId(),
		label,
		type,
		onChange,
	} = props;

	const description = props.description && <Description text={ props.description } id={ id } />;

	const handleCheck = useCallback( ( e ) => {
		if ( ! editable ) return;
		onChange( e.target.checked );
	}, [ onChange, editable ] );

	return (
		<div>
			<CheckSingle
				{ ...attr }
				id={ id }
				onChange={ handleCheck }
				required={ props.required ?? attr.required }
				disabled={ props.disabled ?? attr.disabled }
				readOnly={ ! editable || ( props.readOnly ?? props.readonly ?? attr.readOnly ?? attr.readonly ) }
				label={ <><Label as="span">{ label }</Label>{ props.help && <Help text={ props.help } id={ id } /> }</> }
				checked={ ! isEmpty( props.value ?? props.default ) }
				button={ props.button }
				type={ ( 'switch' === type || 'toggle' === type ) ? 'switch' : 'checkbox' }
			/>
			{ description }
		</div>
	);
}
