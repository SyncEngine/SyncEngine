import React from 'react';
import Form from 'react-bootstrap/Form';
import FloatingLabel from 'react-bootstrap/FloatingLabel';
import { objectToMappable, mapGroupBy, mapSortBy } from "../../utils/format";
import SelectOption from "./SelectOption";
import SelectGroup from "./SelectGroup";

export default function TaskSelector( props ) {

	const {
		choices,
		group,
		onChange,
		label,
		value,
		selectLabel,
		selectValue = selectValue ?? '',
	} = props;

	let options = objectToMappable( choices, 'value' );
	if ( group ) {
		options = mapGroupBy( options, 'module', 'Core' );
		options = objectToMappable( options, 'label', 'options' );
		options = mapSortBy( options, 'label' );
	}

	return (
		<FloatingLabel label={ label }>
			<Form.Select onChange={ ( event ) => { onChange( event.target.value ) } } value={ value }>
				<option value={ selectValue }>{ selectLabel }</option>
				{ ! group &&
				  options.map( ( option, index ) => {
					  return <SelectOption key={ index } {...option}></SelectOption>
				  } )
				}
				{ group &&
				  options.map( ( option, index ) => {
					  return <SelectGroup key={ index } {...option}></SelectGroup>
				  } )
				}
			</Form.Select>
		</FloatingLabel>
	);
}
