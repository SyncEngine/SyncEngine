import React, { createContext, useCallback, useState } from 'react';
import { Card, Collapse, OverlayTrigger, Tooltip } from 'react-bootstrap';
import YAML from 'yaml';

import { HStack, VStack } from '../../partials/Stack';
import Icon from '../../partials/Icon';
import Description from '../Description';
import Label from '../Label';

import useToggle from '../../../hooks/useToggle';
import { createRefId } from '../../../utils/globals';
import { isEmpty } from '../../../utils/conditions';

export const FieldContainerContext = createContext( {} );

export default function FieldContainer( {
	id,
	label,
	icon,
	help,
	description,
	collapsed,
	value,
	children,
	toolbar,
	className = 'shadow-none border-input field-container',
	classHeader = 'px-input',
	classBody = 'p-input-container',
} ) {

	const [ open, toggleOpen, setOpen, setClosed ] = useToggle( ! label ? true : ! collapsed );
	const [ _toolbar, setToolbar ] = useState( toolbar ?? undefined );

	if ( ! id ) {
		id = createRefId();
	}

	const updateToolbar = useCallback( ( element, fieldId ) => {
		if ( React.isValidElement( _toolbar ) || id !== fieldId  ) {
			// Return true if already set, this is dependent on the ID condition due to nesting of fields.
			return id === fieldId;
		}
		// Wrap in timeout to prevent React warning: https://stackoverflow.com/questions/62336340/cannot-update-a-component-while-rendering-a-different-component-warning/71257867#71257867
		setTimeout( () => setToolbar( element ), 0 );
		return true;
	}, [ _toolbar, id ] );

	return (
		<Card className={ className }>
			{ label &&
				<Card.Header
					className={ "bg-transparent btn d-flex justify-content-between border-bottom-0 " + classHeader }
					onClick={ toggleOpen }
					aria-controls={ id + '_container' }
					aria-expanded={ open }
				>
					{ icon &&
						<Icon icon={ icon } className="fs-5 me-2 d-flex align-items-center" />
					}
					<VStack className="text-start lh-sm align-self-center">
						<HStack gap={2}>
							<Label as="span">{ label }</Label>{ help }
						</HStack>
						{ description && <span>{ React.isValidElement( description ) ? description : <Description text={ description } id={ id } /> }</span> }
					</VStack>
					<HStack gap={2}>
						{ React.isValidElement( _toolbar ) && _toolbar }
						{ ( ! open && ! isEmpty( value ) ) &&
							<OverlayTrigger overlay={ <Tooltip id={ id + '_tooltip_value' } className="w-auto"><pre className="text-start">{ YAML.stringify( value ) }</pre></Tooltip> }>
								<Icon icon="configured" className="text-info-emphasis" />
							</OverlayTrigger>
						}
						<Icon icon={ open ? "accordion-close" : "accordion-open" } />
					</HStack>
				</Card.Header>
			}
			<Collapse in={ open } dimension="height" unmountOnExit>
				<Card.Body id={ id + '_container' } className={ classBody }>
					<FieldContainerContext.Provider value={ { open: { toggleOpen: toggleOpen, setOpen: setOpen, setClosed: setClosed }, setToolbar: updateToolbar, id: id } }>
						{ children }
					</FieldContainerContext.Provider>
				</Card.Body>
			</Collapse>
		</Card>
	);
}
