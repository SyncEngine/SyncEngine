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
		handle,
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
				handle: handle = handle ?? 'before',
			} = header;

			switch ( handle ) {
				case 'before':
					headerElements = (
						<>
							<SortableIcon attributes={ { ...attributes, className: "me-3" } } listeners={ listeners } icon={ handleIcon }></SortableIcon>
							{ headerElements }
						</>
					)
					controlsAdded = true;
					break;
				case 'after':
					headerElements = (
						<>
							{ headerElements }
							<SortableIcon attributes={ { ...attributes, className: "me-3" } } listeners={ listeners } icon={ handleIcon }></SortableIcon>
						</>
					)
					controlsAdded = true;
					break;
				case 'container':
					headerAttributes = {
						...headerAttributes,
						...attributes,
						...listeners
					}
					controlsAdded = true;
					break;
				case 'custom':
					headerAttributes.sortableHandle = <SortableIcon attributes={ attributes } listeners={ listeners } icon={ handleIcon }></SortableIcon>;
					controlsAdded = true;
					break;
			}

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
			let {
				component: bodyComponent,
				attributes: bodyAttributes = {},
				children: bodyElements = [],
				handle: handle = handle ?? '',
			} = body;

			switch ( handle ) {
				case 'container':
					bodyAttributes = {
						...bodyAttributes,
						...attributes,
						...listeners
					}
					controlsAdded = true;
					break;
				case 'custom':
					bodyAttributes.sortableHandle = <SortableIcon attributes={ attributes } listeners={ listeners } icon={ handleIcon }></SortableIcon>;
					controlsAdded = true;
					break;
			}

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
