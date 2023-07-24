import React, { useState } from 'react';
import { InputGroup } from 'react-bootstrap';
import { default as ReactSelect } from 'react-select';

import SelectFilters from './SelectFilters';
import { FloatingLabel as FloatingLabelSelect } from './FloatingLabel';
import { objectToMappable, mapGroupBy, mapSortBy, mapFilter, listRenameProp } from '../../../utils/data';
import { isEmpty } from '../../../utils/conditionals';

//import "./styles.scss";

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
			menuPlacement: "auto",
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
		<InputGroup className="w-auto flex-grow-1 bg-body">
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
				getOptionLabel={ option => (
					<span dangerouslySetInnerHTML={{ __html: option.label + ( ( option.description ) ? ' <small class="text-secondary"> - ' + option.description + '</small>' : '' ) }} ></span>
				) }
				theme={ ( theme ) => ( {
					...theme,
					borderRadius: 'var(--bs-default-border-radius)',
					colors: {
						...theme.colors,
						primary: 'var(--bs-secondary-bg)',
						primary75: 'rgba( var(--bs-secondary-bg-rgb), .75 )',
						primary50: 'rgba( var(--bs-secondary-bg-rgb), .5 )',
						primary25: 'rgba( var(--bs-secondary-bg-rgb), .25 )',
						danger: 'var(--bs-danger)',
						dangerLight: 'var(--bs-danger-bg-subtle)',
						neutral0: '',//'var( --reactselect-neutral0 )',
						neutral5: '',//'var( --reactselect-neutral5 )',
						neutral10: '',//'var( --reactselect-neutral10 )',
						neutral20: '',//'var( --reactselect-neutral20 )',
						neutral30: '',//'var( --reactselect-neutral30 )',
						neutral40: '',//'var( --reactselect-neutral40 )',
						neutral50: '',//'var( --reactselect-neutral50 )',
						neutral60: '',//'var( --reactselect-neutral60 )',
						neutral70: '',//'var( --reactselect-neutral70 )',
						neutral80: '',//'var( --reactselect-neutral80 )',
						neutral90: '',//'var( --reactselect-neutral90 )',
					}
				} ) }
				styles={ {
					container: base => ({
						...base,
						backgroundColor: 'var( --bs-body-bg )',
						flex: 1,
					}),
					control: ( base, state ) => ({
						...base,
						height: '100%',
						backgroundColor: 'var( --bs-body-bg )',
						borderColor: ( props.variant ) ? 'var(--bs-' + props.variant + '-border-subtle) !important' : 'var(--bs-input-border-color) !important',
						borderWidth: 'var(--bs-border-width)',
						boxShadow: ( state.isFocused ) ? 'var(--bs-input-focus-box-shadow)' : '',
						'&:hover': {
							borderColor: ( props.variant ) ? 'var(--bs-' + props.variant + ')' : 'var(--bs-input-focus-border-color)',
						},
					}),
					group: base => ({
						...base,
						marginTop: '.5em',
						marginBottom: '.5em',
					}),
					groupHeading: base => ({
						...base,
						paddingTop: '.5em',
						paddingBottom: '.5em',
						backgroundColor: 'var( --bs-secondary-bg-subtle )',
					}),
					menu: base => ({
						...base,
						border: '1px solid var(--bs-input-border-color)',
						borderColor: ( props.variant ) ? 'var(--bs-' + props.variant + '-border-subtle) !important' : 'var(--bs-input-border-color) !important',
						backgroundColor: 'var( --bs-body-bg )',
						marginTop: 0,
						marginBottom: 0,
					})
				} }
			/>
		</InputGroup>
	);
}
