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
		window.app.forms.register( form );
	}, [] );

	// @todo Add tags attribute to pass down parent tags? Or store them inside a global?

	return (
		<>
			<div id={ 'form_' + entity + '_' + id } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
}
