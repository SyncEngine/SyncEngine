import React, { useState } from 'react';
import { InputGroup } from 'react-bootstrap';
import { debounce } from "lodash";
import AsyncSelect from 'react-select/async';

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
		async,
		onAsyncSearch,
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

	const update = ( option ) => {
		if ( option && option.value ) {
			onChange( option.value );
			return;
		}
		onChange( props.default );
	}

	const parseOptions = ( choices, search ) => {
		let options = objectToMappable( choices, 'value', 'label' );
		options = listRenameProp( options, 'name', 'label' );

		if ( filter ) {
			options = mapFilter( options, filterKey, filter );
		}

		if ( search ) {
			options = options.filter( option => option.label.toLowerCase().includes( search.toLowerCase() ) );
		}

		if ( group ) {
			options = mapGroupBy( options, 'module', 'Core' );
			options = objectToMappable( options, 'label', 'options' );
			options = mapSortBy( options, 'label' );
		}

		return options;
	}

	const searchOptions = React.useRef(
		debounce( async ( search, callback ) => {
			callback( parseOptions( await onAsyncSearch( search ) ) );
		}, 500 )
	).current;

	const loadOptions = ( search, callback ) => {
		if ( search && async && onAsyncSearch ) {
			searchOptions( search, callback );
		} else {
			callback( parseOptions( choices, search ) );
		}
	}

	return (
		// z-index 3 to always overlay other input groups.
		<InputGroup className="w-auto flex-grow-1 bg-body z-3">
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
			<AsyncSelect
				{ ...selectProps }
				label={ label }
				placeholder={ placeholder }
				defaultOptions={ parseOptions( choices ) }
				loadOptions={ loadOptions }
				onChange={ update }
				value={ objectToMappable( choices, 'value', 'label' ).filter( option => option.value === value ) }
				isFloating={ ! isEmpty( value ) }
				components={{ Control: FloatingLabelSelect }}
				getOptionLabel={ option => (
					<span dangerouslySetInnerHTML={{ __html: ( option.label ?? option.name ) + ( ( option.description ) ? ' <small class="text-secondary"> - ' + option.description + '</small>' : '' ) }} ></span>
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
						zIndex: 'var(--z-index-floating-label)', // Above Bootstrap floating labels.
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
