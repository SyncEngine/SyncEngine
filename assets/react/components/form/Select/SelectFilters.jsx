import React from 'react';
import { Dropdown, DropdownButton } from 'react-bootstrap';
import DropdownItem from 'react-bootstrap/DropdownItem';
import { objectToMappable } from '../../../utils/format';
import { ucfirst } from '../../../utils/globals';

export default function SelectFilters( props ) {
	let {
		options,
		filters = {},
		filterKey,
		value,
		label,
		align,
		variant = 'outline-secondary',
		className,
		onChange,
	} = props;

	if ( options ) {
		for ( let key in options ) {
			if ( ! options.hasOwnProperty( key ) ) {
				continue;
			}
			if ( options[ key ].hasOwnProperty( filterKey ) ) {
				if ( ! filters.hasOwnProperty( options[ key ][ filterKey ] ) ) {
					filters[ options[ key ][ filterKey ] ] = ucfirst( options[ key ][ filterKey ] );
				}
			}
		}
	}

	return (
		<DropdownButton title={ filters[ value ] ?? label } className={ className } variant={ variant } align={ align }>
			<DropdownItem onClick={ () => onChange('') }>--</DropdownItem>
			{
				objectToMappable( filters, 'value', 'label' ).map( ( filter ) => {
					let {
						label,
						name,
						value
					} = filter;

					value = value ?? name;
					if ( ! label ) {
						label = name ?? value;
					}

					return <Dropdown.Item key={ value } onClick={ () => onChange( value ) }>{ label }</Dropdown.Item>;
				} )
			}
		</DropdownButton>
	);
}
