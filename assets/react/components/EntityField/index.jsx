import React, { useEffect, useState } from 'react';
import FieldsController from "../../controllers/FieldsController";
import FieldController from "../../controllers/FieldController";
import Stack from "react-bootstrap/Stack";
import Card from "react-bootstrap/Card";

export default function EntityField( props ) {
	const {
		value,
		fields,
		onChange,
	} = props;

	const parseEntity = ( val ) => {
		if ( 'object' === typeof val ) {
			val = val.id;
		}
		return ( isNaN( val ) || ! val ) ? '' : val;
	}

	const [ entity, setEntity ] = useState( parseEntity( value ) );
	const [ cache, setCache ] = useState( {} );

	useEffect( () => {
		onChange( cache[ entity ] ?? {} );
	}, [ cache ] );

	useEffect( () => {
		if ( entity ) {
			onChange( { ...cache[ entity ] ?? {}, id: entity } );
		} else {
			onChange( {} );
		}
	}, [ entity ] );

	const updateEntity = ( newValue ) => {
		setEntity( parseEntity( newValue ) );
	}

	const update = ( newValue ) => {
		let newCache = { ...cache };

		newCache[ entity ] = {
			...newValue,
			id: entity,
		}

		setCache( newCache );
	}

	const getEntityFields = () => {
		return fields[ entity ] ?? null;
	}

	return (
		<Stack gap={0}>
			<FieldController
				{...props}
				choices={ {  '': '-- Select --', ...props.choices } }
				value={ entity }
				type="select"
				fields=""
				onChange={ updateEntity }
			/>
			{ getEntityFields() &&
				<Card className="bg-body-tertiary">
					<Card.Body>
						<FieldsController fields={ getEntityFields() } value={ value } onChange={ update } />
					</Card.Body>
				</Card>
			}
		</Stack>
	);
}
