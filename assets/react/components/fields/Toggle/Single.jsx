import React, { useCallback } from 'react';
import { isEmpty } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';
import Description from '../../form/Description';
import Help from '../../form/Help';
import CheckSingle from '../../form/Check/Single';

export default function ToggleSingle( props ) {

	const {
		attr = {},
		id = attr.id ?? createRefId(),
		label,
		type,
		onChange,
	} = props;

	const description = props.description && <Description text={ props.description } id={ id } />;

	const handleCheck = useCallback( ( e ) => {
		onChange( e.target.checked );
	}, [ onChange ] );

	return (
		<div>
			<CheckSingle
				{ ...attr }
				id={ id }
				onChange={ handleCheck }
				required={ props.required ?? attr.required }
				label={ <><span>{ label }</span>{ props.help && <Help text={ props.help } id={ id } /> }</> }
				checked={ ! isEmpty( props.value ?? props.default ) }
				button={ props.button }
				type={ ( 'switch' === type || 'toggle' === type ) ? 'switch' : 'checkbox' }
			/>
			{ description }
		</div>
	);
}
