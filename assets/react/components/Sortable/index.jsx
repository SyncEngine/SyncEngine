import React from 'react';

// DnD Sortable.
import { DndContext, closestCenter, KeyboardSensor, PointerSensor, useSensor, useSensors } from "@dnd-kit/core";
import { arrayMove, SortableContext, sortableKeyboardCoordinates, verticalListSortingStrategy } from "@dnd-kit/sortable";
import { restrictToParentElement, restrictToVerticalAxis } from '@dnd-kit/modifiers';
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
			modifiers={ [
				restrictToParentElement,
				restrictToVerticalAxis,
			] }
		>
			<SortableContext
				items={ items.map( ( item, index ) => index.toString() ) }
				strategy={ verticalListSortingStrategy }
			>
				{ items.map( ( item, index ) => <SortableItem key={ index } index={ index } item={ item } /> ) }
			</SortableContext>
		</DndContext>
	)
}
