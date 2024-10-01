import React, { useState } from 'react';
import { Card, Collapse, OverlayTrigger, Tooltip } from 'react-bootstrap';
import { HStack, VStack } from '../../partials/Stack';
import Icon from '../../partials/Icon';
import Description from '../Description';
import Label from '../Label';
import YAML from 'yaml';
import { createRefId } from '../../../utils/globals';
import { isEmpty } from '../../../utils/conditions';

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
	className = 'shadow-none border-input',
	classHeader = 'px-input',
	classBody = 'p-input-container',
} ) {

	const [ open, setOpen ] = useState( ! label ? true : ! collapsed );

	if ( ! id ) {
		id = createRefId();
	}

	return (
		<Card className={ className }>
			{ label &&
				<Card.Header
					className={ "bg-body btn d-flex justify-content-between border-bottom-0 " + classHeader }
					onClick={ () => { setOpen( ! open ) } }
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
						{ React.isValidElement( toolbar ) && toolbar }
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
					{ children }
				</Card.Body>
			</Collapse>
		</Card>
	);
}
