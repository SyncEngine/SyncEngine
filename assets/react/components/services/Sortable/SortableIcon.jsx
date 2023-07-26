
import React from "react";

export default function SortableIcon( props ) {
	const {
		direction,
		attributes,
		listeners,
	} = props;

	const style = {
		cursor: 'grab',
	};

	return (
		<span {...attributes} {...listeners} style={ style }><span className="bi bi-arrows-expand" /></span>
	)
}
