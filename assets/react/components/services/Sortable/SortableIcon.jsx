
import React from "react";

export default function SortableIcon( props ) {
	const {
		direction,
		attributes,
		listeners,
		icon = 'bi bi-arrows-expand',
		className,
	} = props;

	const style = {
		cursor: 'grab',
	};

	if ( className ) {
		attributes.className += className;
	}

	return (
		<span {...attributes} {...listeners} style={ style }><span className={ icon } /></span>
	)
}
