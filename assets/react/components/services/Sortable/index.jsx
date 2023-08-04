import React from 'react';

// DnD Sortable.
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, TouchSensor, useSensor, useSensors } from "@dnd-kit/core";
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy, horizontalListSortingStrategy } from "@dnd-kit/sortable";
import { restrictToParentElement, restrictToVerticalAxis, restrictToHorizontalAxis } from '@dnd-kit/modifiers';
import SortableItem from "./SortableItem";

export default function Sortable( props ) {

	const {
		setItems,
		items,
		values = items.map( item => item.value ?? item ),
		refs = items.map( item => item._ref ?? item ),
		vertical = true,
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
			<SortableContext
				items={ refs }
				strategy={ ( vertical ) ? verticalListSortingStrategy : horizontalListSortingStrategy }
			>
				{ items.map( ( item, index ) => <SortableItem key={ item._ref ?? index } id={ item._ref ?? index } item={ item } /> ) }
			</SortableContext>
		</DndContext>
	)
}
