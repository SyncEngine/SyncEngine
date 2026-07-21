import React from 'react';
import PropTypes from 'prop-types';
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

SelectGroup.propTypes = {
	label: PropTypes.string,
	name: PropTypes.string,
	choices: PropTypes.object,
	options: PropTypes.object,
};
