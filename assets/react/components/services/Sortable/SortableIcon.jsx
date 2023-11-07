
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

	return (
		<span {...attributes} {...listeners} style={ style } className={ className }><span className={ icon } /></span>
	)
}
