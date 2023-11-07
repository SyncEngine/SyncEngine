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
		children = [],
		handleIcon,
	} = props.item;

	const {
		attributes,
		listeners,
		setNodeRef,
		transform,
		transition,
	} = useSortable( {
		id: props.id.toString(),
		transition: null, // @todo Fix transition.
	} );

	function preventScaling( transform ) {
		if ( ! transform ) {
			return null;
		}

		// prevent scaling above 1
		return {
			...transform,
			scaleX: 1,
			scaleY: 1,
		};
	}

	const style = {
		transform: CSS.Transform.toString( preventScaling( transform ) ),
		transition,
		touchAction: 'none',
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
				handle: handle = 'before',
			} = header;

			if ( 'container' === handle ) {
				headerAttributes = {
					...headerAttributes,
					...attributes,
					...listeners
				}
			} else {

				headerElements = (
					<>
						{ 'before' === handle &&
							<SortableIcon attributes={ { ...attributes, className: "me-3" } } listeners={ listeners } icon={ handleIcon }></SortableIcon>
						}
						{ headerElements }
						{ 'after' === handle &&
							<SortableIcon attributes={ { ...attributes, className: "me-3" } } listeners={ listeners } icon={ handleIcon }></SortableIcon>
						}
					</>
				);
			}
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
			...listeners,
		}
	}

	return React.createElement( component, elemProps, children );
}
