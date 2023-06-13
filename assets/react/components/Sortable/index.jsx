import React, { useEffect, useRef, useState } from 'react';

// DnD Sortable.
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors } from "@dnd-kit/core";
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from "@dnd-kit/sortable";
import { restrictToParentElement, restrictToVerticalAxis } from '@dnd-kit/modifiers';
import SortableItem from "./SortableItem";

export default function Sortable( props ) {
	const {
		setItems,
		items,
		values = items.map( item => item.value ?? item ),
		ids = items.map( item => item.id ?? item ),
	} = props;

	const sensors = useSensors(
		useSensor( PointerSensor ),
		useSensor( KeyboardSensor, {
			coordinateGetter: sortableKeyboardCoordinates,
		} )
	);

	const handleDragEnd = ( event ) => {
		const { active, over } = event;

		if ( active.id !== over.id ) {
			setItems( arrayMove( values, ids.indexOf( active.id ), ids.indexOf( over.id ) ) );
		}
	};

	return (
		<DndContext
			sensors={ sensors }
			collisionDetection={ closestCenter }
			onDragEnd={ handleDragEnd }
			modifiers={ [
				restrictToParentElement,
				restrictToVerticalAxis,
			] }
		>
			<SortableContext
				items={ ids }
				strategy={ verticalListSortingStrategy }
			>
				{ items.map( ( item, index ) => <SortableItem key={ item.id ?? index } id={ item.id ?? index } item={ item } /> ) }
			</SortableContext>
		</DndContext>
	)
}
