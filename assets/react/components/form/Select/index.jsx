import React, { useState } from 'react';
import { InputGroup, Form, FloatingLabel } from 'react-bootstrap';
import { objectToMappable, mapGroupBy, mapSortBy, mapFilter } from '../../../utils/data';
import SelectOption from "./SelectOption";
import SelectGroup from "./SelectGroup";
import SelectFilters from './SelectFilters';

export default function Select( props ) {

	const {
		choices,
		group,
		onChange,
		label,
		value,
		filters,
		filterKey,
		filterLabel,
		filterValue = '',
		filterProps,
		selectLabel,
		selectValue,
		selectProps,
	} = props;

	const [ filter, setFilter ] = useState( filterValue );

	let options = objectToMappable( choices, 'value', 'label' );

	if ( filter ) {
		options = mapFilter( options, filterKey, filter );
	}

	if ( group ) {
		options = mapGroupBy( options, 'module', 'Core' );
		options = objectToMappable( options, 'label', 'options' );
		options = mapSortBy( options, 'label' );
	}

	return (
		<InputGroup className="w-auto">
			{ ( filters || filterKey ) &&
			  <SelectFilters
				  { ...filterProps }
				  options={ choices }
				  filters={ filters }
				  filterKey={ filterKey }
				  value={ filter }
				  label={ filterLabel }
				  onChange={ setFilter }
			  />
			}
			<FloatingLabel label={ label }>
				<Form.Select
					{ ...selectProps }
					onChange={ ( event ) => { onChange( event.target.value ) } }
					value={ value }
				>
					<option value={ selectValue ?? '' }>{ selectLabel }</option>
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
		</InputGroup>
	);
}
