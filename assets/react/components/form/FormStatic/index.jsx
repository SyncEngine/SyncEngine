import React, { forwardRef, useContext, useEffect } from 'react';
import useGlobal from '../../../hooks/useGlobal';
import { TagsContext } from '../../../context/TagsContext';
import { ParentContext } from '../../../context/ParentContext';
import { ContainerContext } from '../../../context/ContainerContext';
import { createRefId } from '../../../utils/globals';
import { deepClone } from '../../../utils/data';

export default forwardRef( function FormStatic( props, ref ) {
	const app = useGlobal();
	const {
		type,
		html,
		footer,
		parentRef,
	} = props;

	const contextRef = createRefId();
	const tagsContext = useContext( TagsContext );
	const parentContext = useContext( ParentContext );
	const containerContext = useContext( ContainerContext );

	// Register context outside of effect to trigger before form init.
	app.context.register( contextRef, {
		tags: deepClone( tagsContext ),
		scope: deepClone( parentContext.scope ?? [] ),
		container: containerContext.current,
	} );

	useEffect( () => {
		const form = document.querySelector( '#form_' + type + '_' + contextRef + ' form' );
		form.id = contextRef;

		app.forms.register( form );

		parentRef.current.element = form;
		parentRef.current.submit = ( callback, params = {} ) => {
			parentRef.current.submitListener = function ( e ) {
				e.preventDefault();
				if ( this.checkValidity() ) {
					form.removeEventListener( 'submit', parentRef.current.submitListener );
					callback( {
						...params,
						form: form,
					} );
				}
				this.reportValidity();
			};
			form.removeEventListener( 'submit', parentRef.current.submitListener );
			form.addEventListener( 'submit', parentRef.current.submitListener, false );
			form.dispatchEvent( new Event( 'submit' ) );
		}

		return () => {
			app.context.clear( contextRef );
			delete parentRef.current.element;
			delete parentRef.current.submit;
			delete parentRef.current.submitListener;
		}
	}, [ contextRef ] );

	return (
		<>
			<div id={ 'form_' + type + '_' + contextRef } ref={ ref } data-context={ contextRef } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
} )
