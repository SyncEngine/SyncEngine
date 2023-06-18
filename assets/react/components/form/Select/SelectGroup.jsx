import React from 'react';
import SelectOption from "./SelectOption";

export default function SelectGroup( props ) {
	let {
		label,
		name,
		options
	} = props;

	if ( ! label ) {
		label = name;
	}

	return (
		<optgroup label={ label }>
			{
				options.map( ( option, index ) => {
					return <SelectOption key={ index } {...option}></SelectOption>
				} )
			}
		</optgroup>
	);
}
