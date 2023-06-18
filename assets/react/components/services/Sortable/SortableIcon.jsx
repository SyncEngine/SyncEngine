
import React from "react";
import { BsArrowDownUp, BsArrowsExpand } from 'react-icons/bs';

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
