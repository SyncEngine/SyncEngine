import React, { forwardRef, useContext, useEffect } from 'react';
import { createRefId } from "../../../utils/globals";
import { TagsContext } from '../../../context/TagsContext';
import { ParentContext } from '../../../context/ParentContext';

export default forwardRef( function FormStatic( props, ref ) {
	const {
		type,
		html,
		footer,
		parentRef,
	} = props;

	const contextRef = createRefId();
	const tags = useContext( TagsContext );
	const parentContext = useContext( ParentContext );

	useEffect( () => {
		const form = document.querySelector( '#form_' + type + '_' + contextRef + ' form' );
		form.id = contextRef;

		window.SyncEngine.forms.register( form );
		window.SyncEngine.context.register( contextRef, {
			tags: structuredClone( tags ),
			scope: structuredClone( parentContext.scope ?? [] ),
		} );

		parentRef.current.element = form;
		parentRef.current.submit = ( callback, params = {} ) => {
			const listener = function ( e ) {
				e.preventDefault();
				if ( this.checkValidity() ) {
					form.removeEventListener( 'submit', listener );
					callback( {
						...params,
						form: form,
					} );
				}
				this.reportValidity();
			};
			form.removeEventListener( 'submit', listener );
			form.addEventListener( 'submit', listener, false );
			form.dispatchEvent( new Event( 'submit' ) );
		}

		return function cleanup() {
			window.SyncEngine.context.clear( contextRef );
		}
	}, [] );

	return (
		<>
			<div id={ 'form_' + type + '_' + contextRef } ref={ ref } data-context={ contextRef } dangerouslySetInnerHTML={{ __html: html }} />
			{ footer }
		</>
	);
} )
