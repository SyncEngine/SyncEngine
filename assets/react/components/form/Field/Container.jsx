import React, { useState } from 'react';
import { Badge, Card, Collapse, OverlayTrigger, Stack, Tooltip } from 'react-bootstrap';
import { createRefId } from '../../../utils/globals';
import { isEmpty } from '../../../utils/conditionals';
import YAML from 'yaml';

export default function FieldContainer( {
	id,
	label,
	help,
	description,
	collapsed,
	value,
	children,
} ) {

	const [ open, setOpen ] = useState( ! label ? true : ! collapsed );

	if ( ! id ) {
		id = createRefId();
	}

	return (
		<Card className="shadow-none border-input">
			{ label &&
				<Card.Header
					className="bg-body btn d-flex justify-content-between border-bottom-0"
					onClick={ () => { setOpen( ! open ) } }
					aria-controls={ 'container-' + id }
					aria-expanded={ open }
				>
					<Stack direction="horizontal" gap={2}>
						<span>{ label }</span>{ help }
					</Stack>
					<Stack direction="horizontal" gap={2}>
						{ ( ! open && ! isEmpty( value ) ) &&
							<OverlayTrigger overlay={ <Tooltip id={ 'tooltip-' + id } className="w-auto"><pre className="text-start">{ YAML.stringify( value ) }</pre></Tooltip> }>
								<Badge pill bg="info" className="icon-link"><span className="bi bi-gear-fill"></span></Badge>
							</OverlayTrigger>
						}
						<span className={ "bi bi-" + ( open ? "chevron-up" : "chevron-down" ) } />
					</Stack>
				</Card.Header>
			}
			<Collapse in={ open } dimension="height">
				<Card.Body id={ 'container-' + id }>
					{ description }
					{ children }
				</Card.Body>
			</Collapse>
		</Card>
	);
}
