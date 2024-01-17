import React from 'react';
import SelectOption from "./SelectOption";
import { objectToMappable } from '../../../utils/data';

export default function SelectGroup( props ) {
	let {
		label,
		name,
		choices,
		options = choices,
	} = props;

	if ( ! label ) {
		label = name;
	}

	return (
		<optgroup label={ label }>
			{
				objectToMappable( options, 'value', 'label' ).map( ( option, index ) => {
					return <SelectOption key={ index } {...option}></SelectOption>
				} )
			}
		</optgroup>
	);
}
