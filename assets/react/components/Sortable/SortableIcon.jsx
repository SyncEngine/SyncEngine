
import { BsArrowDownUp, BsArrowsExpand } from 'react-icons/bs';
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
		<BsArrowsExpand {...attributes} {...listeners} style={ style }></BsArrowsExpand>
	)
}
