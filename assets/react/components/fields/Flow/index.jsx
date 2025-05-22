import React, { useContext, useEffect, useState } from 'react';
import { HStack, VStack } from '../../partials/Stack';
import Entities from '../Entities';
import Icon from '../../partials/Icon';
import { ParentContext } from '../../../context/ParentContext';

export default function Flow( props ) {

	const [ sidebar, setSidebar ] = useState( null );
	const context = useContext( ParentContext );

	const container = context?.container?.closest('.modal-content');

	useEffect( () => {
		if ( ! container ) {
			return;
		}

		if ( sidebar ) {
			container.classList.add('h-100');
		} else {
			container.classList.remove('h-100');
		}
		return () => {
			container.classList.remove('h-100');
		}
	}, [ sidebar, container ] );

	const initSidebar = ( e, context ) => {
		const {
			_ref,
			type,
			entity,
			callbacks,
			entities,
			toolbar,
		} = context;

		setSidebar(
			<VStack className="w-100 overflow-auto">
				<HStack className="justify-content-between sticky-top bg-body p-3 border border-1 border-input mb-2">
					<span className="h4 m-0">{ entity.name }</span>
					<Icon className="icon-btn" icon="close" onClick={ () => setSidebar( null ) } />
				</HStack>
				{ toolbar.actions.config.config }
			</VStack>
		);
	}

	if ( sidebar ) {
		return (
			<div className="position-fixed top-0 start-0 bottom-0 end-0 overflow-hidden bg-body-tertiary" style={ { zIndex: 2000 } }>
				<HStack className="align-items-center h-100 p-3" gap={3}>
					<VStack className="align-self-center w-100 mh-100 overflow-auto">
						<Entities { ...props } onClick={ initSidebar } />
					</VStack>
					{ sidebar }
				</HStack>
			</div>
		);
	}

	return <Entities { ...props } onClick={ initSidebar } />;
}
