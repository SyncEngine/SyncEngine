import React, { useEffect, useState, useRef } from 'react';
import Stack from "react-bootstrap/Stack";
import Card from "react-bootstrap/Card";
import FieldGroup from "../../form/FieldGroup";
import Field from "../../form/Field";

export default function Entity( props ) {
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

	const parseValue = ( val ) => {
		if ( 'object' === typeof val ) {
			return val;
		}
		return ( isNaN( val ) || ! val ) ? {} : { id: val };
	}

	const [ entity, setEntity ] = useState( parseEntity( value ) );
	const [ cache, setCache ] = useState( {} );

	const initialRender = useRef( true );
	useEffect( () => {
		if ( initialRender.current ) {
			initialRender.current = false;
		} else {
			if ( entity ) {
				onChange( { ...cache[ entity ] ?? {}, id: entity } );
			} else {
				onChange( {} );
			}
		}
	}, [ entity, cache ] );

	const updateEntity = ( newValue ) => {
		setEntity( parseEntity( newValue ) );
	}

	const updateFields = ( newValue ) => {
		let newCache = { ...cache };

		newCache[ entity ] = {
			...newValue,
			id: entity,
		}

		setCache( newCache );
	}

	const getEntityFields = () => {
		if ( fields ) {
			return fields[ entity ] ?? null;
		}
		return null;
	}

	return (
		<Stack gap={0}>
			<Field
				{...props}
				value={ entity }
				type="select"
				fields=""
				onChange={ updateEntity }
			/>
			{ getEntityFields() &&
				<Card className="bg-body-tertiary border-top-0">
					<Card.Body>
						<FieldGroup fields={ getEntityFields() } value={ parseValue( value ) } onChange={ updateFields } />
					</Card.Body>
				</Card>
			}
		</Stack>
	);
}
