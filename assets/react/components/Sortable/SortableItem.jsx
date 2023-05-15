import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { BsArrowDownUp } from 'react-icons/bs';

export default function SortableItem( props ) {

	let {
		component,
		attributes: componentAttributes,
		header,
		body,
		children: children = [],
	} = props.item;

	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
	} = useSortable( { id: props.index.toString() } );

	const style = {
		transform: CSS.Transform.toString(transform),
		transition,
	};

	let elemProps = {
		ref: setNodeRef,
		style: style,
		...componentAttributes,
	}

	let controlsAdded = false;

	if ( header ) {

		if ( header.hasOwnProperty( 'component' ) ) {
			let {
				component: headerComponent,
				attributes: headerAttributes = {},
				children: headerElements = [],
			} = header;

			headerElements = (
				<>
					<BsArrowDownUp {...attributes} {...listeners}></BsArrowDownUp>
					{ headerElements }
				</>
			);
			controlsAdded = true;

			header = React.createElement( headerComponent, headerAttributes, headerElements );
		}
		children = (
			<>
				{ header }
				{ children }
			</>
		);
	}

	if ( body ) {

		if ( body.hasOwnProperty( 'component' ) ) {
			const {
				component: bodyComponent,
				attributes: bodyAttributes = {},
				children: bodyElements = [],
			} = body;

			body = React.createElement( bodyComponent, bodyAttributes, bodyElements );
		}

		children = (
			<>
				{ children }
				{ body }
			</>
		);
	}

	if ( ! controlsAdded ) {
		elemProps = {
			ref: setNodeRef,
			style: style,
			...componentAttributes,
			...attributes,
			...listeners
		}
	}

	return React.createElement( component, elemProps, children );
}
