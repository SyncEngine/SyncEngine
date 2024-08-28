import React, { useCallback } from 'react';

import Description from '../../form/Description';
import Help from '../../form/Help';
import { createRefId } from '../../../utils/globals';
import { CheckMulti } from '../../form/Check';
import { isFieldEditable } from '../../../utils/conditions';

export default function Radio( props ) {
	const editable = isFieldEditable( props );

	const {
		label,
		attr = {},
		id = attr.id ?? createRefId(),
		onChange,
	} = props;

	const handleChange = useCallback( ( e ) => {
		onChange( e.target.value );
	}, [ onChange ] );

	return (
		<div>
			<div className="mt-n1 mb-1"><span>{ label }</span>{ props.help && <Help text={ props.help } id={ id } /> }</div>
			{ props.description && <Description text={ props.description } id={ id } /> }
			<CheckMulti
				attr={ attr }
				id={ id }
				onChange={ handleChange }
				choices={ props.choices }
				value={ props.value }
				default={ props.default }
				button={ props.buttons ?? props.button }
				inline={ props.inline }
				vertical={ props.vertical }
				editable={ editable }
				required={ props.required }
				disabled={ props.disabled }
				type={ 'radio' }
			/>
		</div>
	)
}
