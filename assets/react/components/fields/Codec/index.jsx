import React, { useState } from 'react';
import { any, array, bool, func, object, oneOfType, string } from 'prop-types';
import { useTranslation } from 'react-i18next';
import { Card, InputGroup, Stack } from 'react-bootstrap';

import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import Icon from '../../partials/Icon';
import useCodecs from '../../../hooks/useCodecs';
import Fields from '../../form/Fields';
import SelectCodec from '../../form/SelectCodec';
import ModalToggle from '../../services/ModalToggle';
import { isConfigured, isFieldEditable } from '../../../utils/conditions';

export default function Codec( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );

	const {
		onChange,
		view,
	} = props;

	const config = { ...props.value };
	const [ selectedCodec, setSelectedCodec ] = useState( ( config._class ?? '' ) );
	const [ columnTypes ] = useCodecs( props.columnTypes, props.query ?? {} );

	if ( null === columnTypes ) {
		return <LoadingPlaceholder/>
	}

	const selectCodec = ( type ) => {
		setSelectedCodec( type );

		config._class = type;
		onChange( config );
	}

	const updateCodec = ( newConfig ) => {
		if ( selectedCodec ) {
			newConfig._class = selectedCodec;
		}
		onChange( newConfig );
	}

	const getCodecFields = () => {
		if ( columnTypes[ selectedCodec ] ) {
			return {
				...columnTypes[ selectedCodec ].fields ?? null,
			}
		}
		return null;
	}

	const select = (
		<SelectCodec
			options={ columnTypes }
			onChange={ selectCodec }
			value={ selectedCodec }
			label={ props.compact ? null : props.label }
			filters={ props.filters ?? {} }
			editable={ editable }
			disabled={ props.disabled }
		/>
	);

	const fields = getCodecFields();
	const configFields = fields && <Fields fields={ fields } value={ config } onChange={ updateCodec } editable={ editable } />;

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
						  modalProps={ { ...( columnTypes[ selectedCodec ].modalProps ?? {} ) } }
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

Codec.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	label: oneOfType( [ string, object ] ),
	view: string,
	filters: oneOfType( [ object, array ] ),
	compact: bool,
	columnTypes: oneOfType( [ object, array ] ),
}
