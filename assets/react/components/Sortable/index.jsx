import React, { useState } from 'react';

// DnD Sortable.
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors } from "@dnd-kit/core";
import { arrayMove, useSortable, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from "@dnd-kit/sortable";
import { CSS } from "@dnd-kit/utilities";
import SortableItem from "./SortableItem";

export default function Sortable( props ) {
	const {
		items,
		setItems,
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
			setItems( ( items ) => {
				const oldIndex = parseInt( active.id, 10 );
				const newIndex = parseInt( over.id, 10 );

				return arrayMove( items, oldIndex, newIndex );
			} )
		}
	};

	return (
		<DndContext
			sensors={ sensors }
			collisionDetection={ closestCenter }
			onDragEnd={ handleDragEnd }
		>
			<SortableContext
				items={ items }
				strategy={ verticalListSortingStrategy }
			>
				{ items.map( ( item, index ) => <SortableItem key={ index } index={ index } item={ item } /> ) }
			</SortableContext>
		</DndContext>
	)
}
