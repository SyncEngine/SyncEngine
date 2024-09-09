import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Card, InputGroup, Stack } from 'react-bootstrap';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import useColumns from '../../../hooks/useColumns';
import Fields from '../../form/Fields';
import SelectColumn from '../../form/SelectColumn';
import ModalToggle from '../../services/ModalToggle';
import Icon from '../../partials/Icon';
import { isConfigured, isFieldEditable } from '../../../utils/conditions';

export default function Column( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );

	const {
		onChange,
		view,
	} = props;

	const config = { ...props.value };
	const [ selectedColumn, setSelectedColumn ] = useState( ( config._class ?? '' ) );
	const [ columnTypes ] = useColumns( props.columnTypes, props.query ?? {} );

	if ( null === columnTypes ) {
		return <LoadingPlaceholder/>
	}

	const selectColumn = ( type ) => {
		setSelectedColumn( type );

		config._class = type;
		onChange( config );
	}

	const updateColumn = ( webservice ) => {
		if ( selectedColumn ) {
			webservice._class = selectedColumn;
		}
		onChange( webservice );
	}

	const getColumnFields = () => {
		if ( columnTypes[ selectedColumn ] ) {
			return {
				...columnTypes[ selectedColumn ].fields ?? null,
			}
		}
		return null;
	}

	const select = (
		<SelectColumn
			options={ columnTypes }
			onChange={ selectColumn }
			value={ selectedColumn }
			label={ props.compact ? null : props.label }
			filters={ props.filters ?? {} }
			editable={ editable }
			disabled={ props.disabled }
		/>
	);

	const fields = getColumnFields();
	const configFields = fields && <Fields fields={ fields } value={ config } onChange={ updateColumn } editable={ editable } />;

	const form = (
		<Stack gap={0}>
			{ ! configFields ? select :
				<Card className="bg-body border-0">
					<Card.Header className="border-0 p-0">{ select }</Card.Header>
					<Card.Body className="border p-3">{ configFields }</Card.Body>
				</Card>
			}
		</Stack>
	);
	let component;

	switch ( view ) {
		case 'full':
			component = form
			break;
		default:
			component = (
				<InputGroup className="flex-nowrap">
					{ select }
					{ configFields &&
					  <ModalToggle
						  modalProps={ { ...( columnTypes[ selectedColumn ].modalProps ?? {} ) } }
						  trigger={
							  <InputGroup.Text role="button">
								  <Icon icon={ isConfigured( { ...config, _class: null } ) ? "configured" : "config" } />
							  </InputGroup.Text>
						  }
					  >
						  { form }
					  </ModalToggle>
					}
				</InputGroup>
			);
			break;
	}

	return <div className="flex-grow-1">{ component }</div>;
}
