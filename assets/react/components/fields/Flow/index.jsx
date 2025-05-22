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
			<VStack className="p-3 w-100">
				<HStack className="justify-content-between sticky-top bg-body">
					<span className="h4">{ entity.name }</span>
					<Icon className="icon-btn" icon="close" onClick={ () => setSidebar( null ) } />
				</HStack>
				{ toolbar.actions.config.config }
			</VStack>
		);
	}

	if ( sidebar ) {
		return (
			<div className="vh-100">
				<div className="position-fixed top-0 start-0 bottom-0 end-0 overflow-auto bg-body" style={ { zIndex: 2000 } }>
					<HStack className="align-items-start">
						<div className="p-3 w-100 sticky-top">
							<Entities { ...props } onClick={ initSidebar } />
						</div>
						{ sidebar }
					</HStack>
				</div>
			</div>
		);
	}

	return <Entities { ...props } onClick={ initSidebar } />;
}
