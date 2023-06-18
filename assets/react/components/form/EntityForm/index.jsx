import React from "react";

export default function EntityForm( props ) {
	const {
		id,
		entity,
		html,
		footer,
	} = props;

	return (
		<>
			<div id={ 'edit_' + entity + '_' + id } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
}
