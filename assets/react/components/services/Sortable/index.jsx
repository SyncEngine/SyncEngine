import React from 'react';

// DnD Sortable.
import {
	closestCenter,
	DndContext,
	KeyboardSensor,
	PointerSensor,
	TouchSensor,
	useSensor,
	useSensors,
} from '@dnd-kit/core';
import {
	arrayMove,
	horizontalListSortingStrategy,
	SortableContext,
	sortableKeyboardCoordinates,
	verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { restrictToHorizontalAxis, restrictToParentElement, restrictToVerticalAxis } from '@dnd-kit/modifiers';
import SortableItem from './SortableItem';
import { Table } from 'react-bootstrap';

export default function Sortable( props ) {

	const {
		setItems,
		items,
		values = items.map( item => item.value ?? item ),
		refs = items.map( item => item._ref ?? item ),
		vertical = true,
		view,
	} = props;

	const sensors = useSensors(
		useSensor( PointerSensor, {
			activationConstraint: {
				distance: 10, // Enable sort function when dragging 10px
			}
		} ),
		useSensor( TouchSensor, {
			activationConstraint: {
				distance: 10, // Enable sort function when dragging 10px
			}
		} ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} ),
	);

	const handleDragEnd = ( event ) => {
		const { active, over } = event;

		// SortableItem uses id instead of _ref.
		if ( active.id !== over.id ) {
			setItems( arrayMove( values, refs.indexOf( active.id ), refs.indexOf( over.id ) ) );
		}
	};

	const sortable = (
		<SortableContext
			items={ refs }
			strategy={ ( vertical ) ? verticalListSortingStrategy : horizontalListSortingStrategy }
		>
			{ items.map( ( item, index ) => <SortableItem key={ item._ref ?? index } id={ item._ref ?? index } item={ item } /> ) }
		</SortableContext>
	);

	let component = sortable;

	switch ( view ) {
		case 'table':
			component = (
				<Table { ...props.table }>
					{ props.thead }
					<tbody>
						{ sortable }
					</tbody>
					{ props.tfoot }
				</Table>
			)
			break;
	}

	return (
		<DndContext
			sensors={ sensors }
			collisionDetection={ closestCenter }
			onDragEnd={ handleDragEnd }
			modifiers={ [
				restrictToParentElement,
				( vertical ) ? restrictToVerticalAxis : restrictToHorizontalAxis,
			] }
		>
			{ component }
		</DndContext>
	)
}
