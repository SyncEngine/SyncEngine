import React from "react";

export default function FormStatic( props ) {
	const {
		id,
		entity,
		html,
		footer,
	} = props;

	return (
		<>
			<div id={ 'form_' + entity + '_' + id } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
}
