import React, { useState } from 'react';
import { Accordion, Button, Modal, Stack, Tabs } from "react-bootstrap";
import Sortable from "../components/Sortable";
import Card from "react-bootstrap/Card";
import { BiPencil, BiTrash } from "react-icons/bi";

export default function FlowController( props ) {
	const {
		args = {},
		value = {},
		onChange,
	} = props;

	const {
		steps,
	} = args;

	const [ order, setOrder ] = useState( value );

	const updateOrder = ( steps ) => {
		setOrder( order );
		onChange( value );
	}

	const openEditModal = ( id ) => {

	}

	const openDeleteModal = ( id ) => {

	}

	return (
		<Stack>
			<Sortable
				setItems={ updateOrder }
				items={
					steps.map( step => {
						return {
							id: step.id,
							component: Card,
							attributes: {
								eventKey: step.id,
							},
							body: (
								<Card.Body>
									<Card.Title>{ step.name }</Card.Title>
									<Card.Text>{ step.description }</Card.Text>
									<Button variant="primary" onClick={ () => openEditModal( step.id ) }><BiPencil></BiPencil></Button>
									<Button variant="danger" onClick={ () => openDeleteModal( step.id ) }><BiTrash></BiTrash></Button>
								</Card.Body>
							)
						};
					} )
				}
			/>
		</Stack>
	);
}
