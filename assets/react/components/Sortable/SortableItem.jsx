import React from 'react';
import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import SortableIcon from "./SortableIcon";

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
	} = useSortable( {
		id: props.index.toString(),
		transition: null, // @todo Fix transition.
	} );

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
					<SortableIcon attributes={ { ...attributes, className: "me-3" } } listeners={listeners}></SortableIcon>
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
