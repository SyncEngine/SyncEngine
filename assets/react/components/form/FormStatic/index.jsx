import React, { useContext, useEffect } from 'react';
import { createRefId } from "../../../utils/globals";
import { TagsContext } from '../../../context/TagsContext';
import { EntityContext } from '../../../context/EntityContext';

export default function FormStatic( props ) {
	const {
		id,
		entity,
		html,
		footer,
	} = props;

	const contextRef = createRefId();
	const tags = useContext( TagsContext );
	const parent = useContext( EntityContext );

	useEffect( () => {
		const form = document.querySelector( '#form_' + entity + '_' + id + ' form' );
		form.id = contextRef;
		window.app.forms.register( form );

		window.app.context.register( contextRef, {
			tags: structuredClone( tags ),
			execute: structuredClone( tags.context ),
			parentEntity: parent,
		} );

		return function cleanup() {
			window.app.context.clear( contextRef );
		}
	}, [] );

	return (
		<>
			<div id={ 'form_' + entity + '_' + id } data-context={ contextRef } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
}
