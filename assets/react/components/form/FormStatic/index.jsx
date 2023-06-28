import React, { useEffect } from "react";
import { createRefId } from "../../../utils/globals";

export default function FormStatic( props ) {
	const {
		id,
		entity,
		html,
		footer,
	} = props;

	useEffect( () => {
		const form = document.querySelector( '#form_' + entity + '_' + id + ' form' );
		form.id = createRefId();
		beforeUnloadForm( form );
	}, [] );

	return (
		<>
			<div id={ 'form_' + entity + '_' + id } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
}
