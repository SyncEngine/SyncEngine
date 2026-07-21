import React, { useCallback } from 'react';
import PropTypes from 'prop-types';
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
				toggle={ props.toggle }
				button={ props.button }
				type={ ( 'switch' === type || 'toggle' === type ) ? 'switch' : 'checkbox' }
			/>
			{ description }
		</div>
	);
}

ToggleSingle.propTypes = {
	attr: PropTypes.object,
	id: PropTypes.string,
	label: PropTypes.string,
	type: PropTypes.string,
	onChange: PropTypes.func,
	required: PropTypes.bool,
	disabled: PropTypes.bool,
	readOnly: PropTypes.bool,
	value: PropTypes.any,
	default: PropTypes.any,
	help: PropTypes.string,
	description: PropTypes.string,
	toggle: PropTypes.array,
	button: PropTypes.bool,
};
