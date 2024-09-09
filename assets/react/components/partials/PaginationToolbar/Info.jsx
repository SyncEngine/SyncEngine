import React from 'react';
import { Form, Stack } from 'react-bootstrap';
import { useTranslation } from 'react-i18next';
import Icon from '../Icon';

export default function PaginationInfo( props ) {
	const { t } = useTranslation();

	const {
		callbackSetLimit,
		numItems,
		totalItems,
		limit,
		offset = 0,
		defaultLimit,
		size,
		className = 'text-secondary',
	} = props;

	return (
		<Stack direction="horizontal" className={ className + ( 'sm' === size ? ' small' : '' ) }>
			{ numItems ? <>{ t( 'Showing' ) } { offset+1 }-{ offset+numItems } / { totalItems }</> : totalItems }
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
						<option value={ defaultLimit }>{ defaultLimit }</option>
						<option value={ defaultLimit * 2 }>{ defaultLimit * 2 }</option>
						<option value={ defaultLimit * 4 }>{ defaultLimit * 4 }</option>
						<option value={ defaultLimit * 10 }>{ defaultLimit * 10 }</option>
					</Form.Select>
					:
					limit
			}
			{ ( numItems > limit ) &&
			    <small className="px-1">
				    <span className="align-middle">+{ numItems - limit }</span>
				    <Icon
						icon="reset"
					    className="px-1 icon-btn"
					    onClick={ () => { callbackSetLimit( limit ) } }
				    />
			    </small>
			}
		</Stack>
	)
}
