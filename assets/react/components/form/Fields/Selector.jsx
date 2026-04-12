import React, { useMemo } from 'react';
import { useTranslation } from 'react-i18next';

import Select from '../../fields/Select/Advanced';

export default function Selector( {
	fields,
	onAdd,
	className = 'position-sticky top-0 z-1 bg-body border border-input rounded p-2',
} ) {
	const { t } = useTranslation();

	const choices = useMemo( () => {
		return ( fields ?? [] ).map( ( field ) => {
			const name = field.name;
			return {
				value: name,
				label: field.label ?? name,
				description: field.description,
			};
		} ).filter( ( option ) => option.value );
	}, [ fields ] );

	if ( ! choices.length ) {
		return null;
	}

	return (
		<div className={ className }>
			<Select
				choices={ choices }
				label={ t( 'Add field' ) }
				placeholder={ '-- ' + t( 'Select field' ) + ' --' }
				value=""
				onChange={ ( value ) => value && onAdd( value ) }
				selectProps={ {
					isClearable: false,
					menuPlacement: 'auto',
				} }
			/>
		</div>
	);
}
