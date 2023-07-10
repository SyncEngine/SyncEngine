import React, { useState } from 'react';
import { InputGroup, Form, FloatingLabel } from 'react-bootstrap';
import { objectToMappable, mapGroupBy, mapSortBy, objectFilter } from '../../../utils/format';
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
		filterClass,
		selectLabel,
		selectValue,
		selectClass,
	} = props;

	const [ filter, setFilter ] = useState( filterValue );

	let options = objectToMappable( choices, 'value', 'label' );
	if ( group ) {
		options = mapGroupBy( options, 'module', 'Core' );
		options = objectToMappable( options, 'label', 'options' );
		options = mapSortBy( options, 'label' );
	}

	return (
		<InputGroup>
			{ ( filters || filterKey ) &&
			  <SelectFilters
				  options={ choices }
				  filters={ filters }
				  filterKey={ filterKey }
				  value={ filter }
				  label={ filterLabel }
				  className={ filterClass }
				  onChange={ setFilter }
			  />
			}
			<FloatingLabel label={ label }>
				<Form.Select onChange={ ( event ) => { onChange( event.target.value ) } } value={ value } className={ selectClass }>
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
