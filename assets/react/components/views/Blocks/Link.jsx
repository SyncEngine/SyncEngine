import React from 'react';
import { sprintf } from '../../../utils/globals';

export default function Link( props ) {
	const {
		item,
		text,
		href,
	} = props;

	if ( ! href ) {
		return;
	}

	return (
		<>
			<a className="icon-link me-1" href={ sprintf( href, item ) } target="_blank">
				<i className="bi bi-link-45deg"></i>
			</a>
			{ sprintf( text ?? href, item ) }
		</>
	)
}
