import React, { useState } from 'react';
import { InputGroup } from 'react-bootstrap';
import { default as ReactSelect } from 'react-select';

import SelectFilters from './SelectFilters';
import { FloatingLabel as FloatingLabelSelect } from './FloatingLabel';
import { objectToMappable, mapGroupBy, mapSortBy, mapFilter, listRenameProp } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';

export default function Select( props ) {

	const {
		choices,
		group,
		onChange,
		label,
		placeholder,
		value,
		variant,
		filters,
		filterKey,
		filterLabel,
		filterValue = '',
		filterProps,
		selectProps = {
			isClearable: true,
			isSearchable: true,
		},
	} = props;

	const [ filter, setFilter ] = useState( filterValue );

	let options = objectToMappable( choices, 'value', 'label' );
	options = listRenameProp( options, 'name', 'label' );

	const currentValue = options.filter( option => option.value === value );

	if ( filter ) {
		options = mapFilter( options, filterKey, filter );
	}

	if ( group ) {
		options = mapGroupBy( options, 'module', 'Core' );
		options = objectToMappable( options, 'label', 'options' );
		options = mapSortBy( options, 'label' );
	}

	const update = ( option ) => {
		if ( option && option.value ) {
			onChange( option.value );
			return;
		}
		onChange( props.default );
	}

	return (
		<InputGroup className="w-auto flex-grow-1">
			{ ( filters || filterKey ) &&
			  <SelectFilters
				  { ...filterProps }
				  options={ choices }
				  filters={ filters }
				  filterKey={ filterKey }
				  value={ filter }
				  label={ filterLabel }
				  onChange={ setFilter }
				  variant={ variant }
			  />
			}
			<ReactSelect
				{ ...selectProps }
				label={ label }
				placeholder={ placeholder }
				options={ options }
				components={{ Control: FloatingLabelSelect }}
				onChange={ update }
				value={ currentValue }
				isFloating={ ! isEmpty( value ) }
				styles={ {
					container: base => ({
						...base,
						flex: 1,
					}),
					control: ( base, state ) => ({
						...base,
						height: '100%',
						borderRadius: 'var(--bs-default-border-radius)',
						borderColor: ( props.variant ) ? 'var(--bs-' + props.variant + '-border-subtle)' : 'var(--bs-input-border-color)',
						boxShadow: ( state.isFocused ) ? 'var(--bs-input-focus-box-shadow)' : '',
						'&:hover': {
							borderColor: ( props.variant ) ? 'var(--bs-' + props.variant + ')' : 'var(--bs-input-focus-border-color)',
						},
					}),
					menu: base => ({
						...base,
						marginTop: 0,
						borderRadius: 'var(--bs-default-border-radius)',
						zIndex: 3,
					})
				} }
			/>
		</InputGroup>
	);
}
