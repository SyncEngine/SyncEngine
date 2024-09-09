import React, { useState } from 'react';
import { Card, Collapse, OverlayTrigger, Stack, Tooltip } from 'react-bootstrap';
import { createRefId } from '../../../utils/globals';
import { isEmpty } from '../../../utils/conditions';
import YAML from 'yaml';
import Description from '../Description';
import Icon from '../../partials/Icon';
import Label from '../Label';

export default function FieldContainer( {
	id,
	label,
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
					<Stack className="text-start lh-sm align-self-center">
						<Stack direction="horizontal" gap={2}>
							<Label as="span">{ label }</Label>{ help }
						</Stack>
						{ description && <span>{ React.isValidElement( description ) ? description : <Description text={ description } id={ id } /> }</span> }
					</Stack>
					<Stack direction="horizontal" gap={2}>
						{ React.isValidElement( toolbar ) && toolbar }
						{ ( ! open && ! isEmpty( value ) ) &&
							<OverlayTrigger overlay={ <Tooltip id={ id + '_tooltip_value' } className="w-auto"><pre className="text-start">{ YAML.stringify( value ) }</pre></Tooltip> }>
								<Icon icon="config" className="text-info-emphasis" />
							</OverlayTrigger>
						}
						<Icon icon={ open ? "chevron-up" : "chevron-down" } />
					</Stack>
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
