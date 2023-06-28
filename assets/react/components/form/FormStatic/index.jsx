import React, { useEffect } from "react";

export default function FormStatic( props ) {
	const {
		id,
		entity,
		html,
		footer,
	} = props;

	useEffect( () => {
		beforeUnloadForm( document.querySelector( '#form_' + entity + '_' + id + ' form' ) );
	}, [] );

	return (
		<>
			<div id={ 'form_' + entity + '_' + id } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
}
