import React from 'react';
import TagsGroup from './Group';
import { OverlayTrigger, Popover } from 'react-bootstrap';

export default function Tags( props ) {

	const {
		startChar = '{{ ',
		endChar = ' }}',
		separator = '.',
		entity,
		context,
		onClick,
		trigger,
	} = props;

	const selectTag = ( tag ) => {
		onClick( startChar + tag + endChar );
	}

	const list = {};

	const body = <TagsGroup list={list} onClick={onClick} separator={ separator }/>;

	if ( trigger ) {
		const popover = (
			<Popover>
				<Popover.Header>Select tag</Popover.Header>
				<Popover.Body>{body}</Popover.Body>
			</Popover>
		);

		return (
			<OverlayTrigger trigger="click" overlay={popover}>
				{trigger}
			</OverlayTrigger>
		);
	}

	return body;
}
