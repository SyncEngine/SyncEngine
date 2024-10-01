import React, { useCallback, useState } from 'react';
import { default as AsyncSelect } from 'react-select/async';
import { InputGroup } from 'react-bootstrap';

import { HStack } from '../../partials/Stack';
import SelectFilters from './SelectFilters';
import Icon from '../../partials/Icon';
import { FloatingLabel as FloatingLabelSelect } from './FloatingLabel';
import { listRenameProp, mapFilter, mapGroupBy, mapSortBy, objectToMappable } from '../../../utils/data';
import { isEmpty, isFieldEditable } from '../../../utils/conditions';
import { debounce } from '../../../utils/events';

//import "./styles.scss";

function optionLabel( option ) {
	return (
		<HStack gap={ 2 }>
			{ option.icon && <Icon icon={ option.icon }/> }
			<span>{ option.label ?? option.name }</span>
			{ option.description && <small className="text-secondary"> - { option.description }</small> }
		</HStack>
	)
}

export default function SelectAdvanced( props ) {
	const editable = isFieldEditable( props );

	const {
		required = false,
		choices,
		group,
		onChange,
		async,
		onAsyncSearch,
		label,
		placeholder,
		value,
		variant,
		filters = {},
		selectProps = {
			isClearable: editable && isEmpty( required ),
			isSearchable: editable,
			isDisabled: ! isEmpty( props.disabled ),
			isEditable: editable,
			menuPlacement: "auto",
			menuIsOpen: ! editable ? false : undefined,
		},
		compact = isEmpty( label )
	} = props;

	const [ filter, setFilter ] = useState( filters.value );

	const update = ( option ) => {
		if ( ! editable ) {
			return;
		}

		if ( option && option.value ) {
			onChange( option.value );
			return;
		}
		onChange( props.default );
	}

	const parseOptions = useCallback( ( choices, search = null ) => {
		let options = objectToMappable( choices, 'value', 'label' );
		// In case it already was an array, map the entity id as value.
		options = listRenameProp( options, 'id', 'value' );
		// Make sure we have a label.
		options = listRenameProp( options, 'name', 'label' );

		if ( filter ) {
			options = mapFilter( options, filters.key, filter );
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
	}, [ filters.key, filter ] );

	const loadOptions = useCallback( ( search, callback ) => {
		if ( search && async && onAsyncSearch ) {
			searchOptions( search, callback );
		} else {
			callback( parseOptions( choices, search ) );
		}
	}, [ choices ] );

	const searchOptions = React.useRef(
		debounce( async ( search, callback ) => {
			callback( parseOptions( await onAsyncSearch( search ) ) );
		}, 500 )
	).current;

	const compactStyles = compact ? {
		padding: 0,
		margin: 0,
	} : {};

	return (
		// z-index 3 to always overlay other input groups.
		<InputGroup className="w-auto flex-grow-1 flex-basis-0 bg-body">
			{ ( editable && ! isEmpty( filters ) ) &&
			  <SelectFilters
				  { ...filters.props }
				  options={ choices }
				  //filters={ filters } @todo, multi filters?
				  filterKey={ filters.key }
				  value={ filter }
				  label={ filters.label }
				  onChange={ setFilter }
				  variant={ variant }
			  />
			}
			<AsyncSelect
				{ ...selectProps }
				label={ label }
				placeholder={ placeholder }
				defaultOptions={ editable && parseOptions( choices ) }
				loadOptions={ editable && loadOptions }
				onChange={ update }
				value={ objectToMappable( choices, 'value', 'label' ).filter( option => String( option.value ) === String( value ) ) }
				isFloating={ ! isEmpty( value ) }
				components={ { Control: FloatingLabelSelect } }
				getOptionLabel={ optionLabel }
				theme={ ( theme ) => ( {
					...theme,
					borderRadius: 'var(--bs-border-radius)',
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
						minHeight: 'auto',
						height: '100%',
						backgroundColor: 'var( --bs-body-bg )',
						borderColor: ( props.variant ) ? 'var(--bs-' + props.variant + '-border-subtle) !important' : 'var(--bs-input-border-color) !important',
						borderWidth: 'var(--bs-border-width)',
						boxShadow: ( state.isFocused ) ? 'var(--bs-input-focus-box-shadow)' : '',
						'&:hover:not([readonly])': {
							borderColor: ( props.variant ) ? 'var(--bs-' + props.variant + ') !important' : 'var(--bs-input-focus-border-color)',
						},
					}),
					placeholder:  base => ({
						...base,
						textWrap: 'nowrap',
						overflow: 'hidden',
						textOverflow: 'ellipsis',
					}),
					input:  base => ({ ...base, ...compactStyles, }),
					dropdownIndicator: base => ({ ...base, ...compactStyles, }),
					clearIndicator: base => ({ ...base, ...compactStyles, }),
					loadingIndicator: base => ({ ...base, ...compactStyles, }),
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
						zIndex: 9999,
					}),
					menuPortal: base => ({
						...base,
						zIndex: 9999,
					})
				} }
				menuPortalTarget={ document.body }
				menuPosition='fixed'
			/>
		</InputGroup>
	);
}
