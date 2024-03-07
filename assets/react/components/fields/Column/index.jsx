import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { InputGroup } from 'react-bootstrap';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import useColumns from '../../../hooks/useColumns';
import Fields from '../../form/Fields';
import OverlayToggle from '../../services/OverlayToggle';
import SelectSimple from '../Select/Simple';

export default function Column( props ) {
	const { t } = useTranslation();

	const {
		onChange,
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

	const fields = getColumnFields();

	return (
		<div className="flex-grow-1">
			<InputGroup className="flex-nowrap">
				<SelectSimple options={ columnTypes } onChange={ selectColumn } value={ selectedColumn } />
				{ fields &&
					<OverlayToggle overlay={ <div><Fields fields={ fields } value={ config } onChange={ updateColumn } /></div> }>
						<InputGroup.Text role="button">
							<span className="bi bi-gear" />
						</InputGroup.Text>
					</OverlayToggle>
				}
			</InputGroup>
		</div>
	);
}
