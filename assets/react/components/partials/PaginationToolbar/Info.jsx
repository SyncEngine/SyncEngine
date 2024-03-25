import React from 'react';
import { Form, Stack } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';

export default function PaginationInfo( props ) {
	const { t } = useTranslation();

	const {
		callbackSetLimit,
		numItems,
		totalItems,
		limit,
		size,
		className = 'text-secondary',
	} = props;

	return (
		<Stack direction="horizontal" className={ className + ( 'sm' === size ? ' small' : '' ) }>
			{ numItems ? <>{ t( 'Showing' ) } { numItems } / { totalItems }</> : totalItems }
			<span className="px-2">|</span>
			{ t( 'Per page' ) }:
			{
				callbackSetLimit ?
					<Form.Select
						className="ms-1 d-inline-block w-auto"
						value={ limit }
						size={ size }
						onChange={ ( e ) => { callbackSetLimit( e.target.value ) } }
					>
						<option value={ limit * 2 }>{ limit * 2 }</option>
						<option value={ limit * 2 }>{ limit * 2 }</option>
						<option value={ limit * 4 }>{ limit * 4 }</option>
						<option value={ limit * 10 }>{ limit * 10 }</option>
					</Form.Select>
					:
					limit
			}
		</Stack>
	)
}
