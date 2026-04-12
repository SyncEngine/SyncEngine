import React, { useContext, useMemo, useState } from 'react';
import { Alert, Col, Stack } from 'react-bootstrap';

import FieldsItem from './Item';
import Selector from './Selector';
import { FieldsContext } from '../../../context/FieldsContext';
import { objectToMappable } from '../../../utils/data';
import { createRefId } from '../../../utils/globals';
import useConditions from '../../../hooks/useConditions';
import { isEmpty, isValue } from '../../../utils/conditions';

export default function Group( props ) {
	const {
		fields,
		updateField,
		inline = false,
		editable = true,
	} = props;

	const fieldsContext = useContext( FieldsContext );

	if ( ! fields ) {
		return <Alert variant="warning">No fields found.</Alert>
	}

	const mappedFields = useMemo( () => objectToMappable( fields, 'name' ), [ fields ] );
	const values = fieldsContext?.values ?? {};

	const canHideField = ( field ) => {
		const visibility = field?.visibility ? String( field.visibility ).toLowerCase() : 'always';

		if ( 'always' === visibility || inline || ! isEmpty( field.required ) || isValue( values[ field.name ] ) ) {
			return false;
		}

		return 'optional' === visibility || 'hidden' === visibility;
	};

	const [ selectedFieldNames, setSelectedFieldNames ] = useState( [] );

	const selectedNameMap = useMemo( () => {
		return selectedFieldNames.reduce( ( map, name ) => {
			map[ name ] = true;
			return map;
		}, {} );
	}, [ selectedFieldNames ] );

	const visibleFields = mappedFields.filter( ( field ) => ! canHideField( field ) || selectedNameMap[ field.name ] );
	const hiddenFields = mappedFields.filter( ( field ) => canHideField( field ) && ! selectedNameMap[ field.name ] );

	const canSelectFields = ! inline && hiddenFields.length > 0;

	let elements = visibleFields.map( ( field, index ) => {

		field.id = field.id ?? createRefId();

		let item = <FieldsItem key={ field.id } updateField={ updateField } field={ field } editable={ editable } />;

		if ( 'fixed' === inline ) {
			item = <Col className={ field.col ?? props.col }>{ item }</Col>
		}
		return <ConditionalField key={ field.id + index } field={ field }>{ item }</ConditionalField>;

	} );

	if ( ! elements.length && ! canSelectFields ) {
		return;
	}

	return (
		<Stack gap={ props.gap || 2 } direction={ inline ? 'horizontal' : 'vertical' }>
			{ canSelectFields &&
				<Selector
					fields={ hiddenFields }
					onAdd={ ( name ) => setSelectedFieldNames( ( selected ) => [ ...new Set( [ ...selected, name ] ) ] ) }
				/>
			}
			{ elements }
		</Stack>
	);
}

const ConditionalField = ( props ) => {
	const {
		field
	} = props;

	const [ valid ] = useConditions( field.conditions );

	if ( ! valid ) {
		return;
	}

	return props.children;
}
