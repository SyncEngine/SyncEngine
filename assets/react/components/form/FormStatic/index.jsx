import React, { useContext, useEffect } from 'react';
import { createRefId } from "../../../utils/globals";
import { TagsContext } from '../../../context/TagsContext';

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

	// @todo Global context manager.
	const tags = useContext( TagsContext );

	return (
		<>
			<div id={ 'form_' + entity + '_' + id } data-tags={ JSON.stringify( { context: tags.context ?? {} } ) } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
}
