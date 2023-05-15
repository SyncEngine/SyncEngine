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
			setItems( ( tasks ) => {
				const oldIndex = tasks.indexOf( active.id );
				const newIndex = tasks.indexOf( over.id );

				return arrayMove( tasks, oldIndex, newIndex );
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
				{ items.map( ( item, id ) => <SortableItem key={ id } id={ id } item={ item } /> ) }
			</SortableContext>
		</DndContext>
	)
}
