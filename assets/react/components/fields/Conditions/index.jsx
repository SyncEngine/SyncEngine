import React, { useEffect, useRef, useState, useMemo } from 'react';
import { any, array, bool, func, object, oneOfType } from 'prop-types';
import { useTranslation } from 'react-i18next';
import { ButtonGroup, Button } from 'react-bootstrap';

import ConditionsGroups from './ConditionsGroups';
import { getOperators, isFieldEditable } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';

function parseValue( value ) {
	if ( ! value ) {
		return {
			groups: [ { _ref: createRefId(), operator: 'AND', conditions: [] } ],
			operator: 'AND',
		};
	}

	if ( Array.isArray( value ) ) {
		return {
			groups: [ { _ref: createRefId(), operator: 'AND', conditions: value } ],
			operator: 'AND',
		};
	}

	if ( ! value.groups ) {
		value.groups = [];
	}
	if ( ! value.operator ) {
		value.operator = 'AND';
	}

	value.groups = value.groups.map( ( group ) => ( {
		_ref: group._ref || createRefId(),
		operator: group.operator || 'AND',
		conditions: group.conditions || [],
	} ) );

	if ( ! value.groups.length ) {
		value.groups.push( { _ref: createRefId(), operator: 'AND', conditions: [] } );
	}

	return value;
}

function transformOutput( data ) {
	if ( 1 === data.groups.length && 'AND' === data.groups[0].operator ) {
		return data.groups[0].conditions;
	}

	return {
		groups: data.groups.map( ( g ) => ( {
			operator: g.operator,
			conditions: g.conditions,
		} ) ),
		operator: data.operator,
	};
}

export default function Conditions( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );

	const {
		conditionTypes = {},
		value,
		source = {},
		onChange,
	} = props;

	const localUpdateRef = useRef( false );
	const upstreamValueRef = useRef( null );

	const [ data, setData ] = useState( () => parseValue( value ) );

	useEffect( () => {
		if ( localUpdateRef.current ) {
			localUpdateRef.current = false;
			return;
		}
		const parsed = parseValue( value );
		if ( value !== upstreamValueRef.current ) {
			setData( parsed );
			upstreamValueRef.current = value;
		}
	}, [ value ] );

	const updateValue = ( newData ) => {
		const output = transformOutput( newData );
		localUpdateRef.current = true;
		upstreamValueRef.current = value;
		onChange( output );
		setData( newData );
	};

	return (
		<ConditionsGroups
			groups={ data.groups }
			operator={ data.operator }
			onGroupsChange={ ( newGroups ) => updateValue( { ...data, groups: newGroups } ) }
			onOperatorChange={ ( newOperator ) => updateValue( { ...data, operator: newOperator } ) }
			onConditionsChange={ ( index, newConditions ) => {
				const newGroups = [ ...data.groups ];
				newGroups[ index ] = { ...newGroups[ index ], conditions: newConditions };
				updateValue( { ...data, groups: newGroups } );
			} }
			editable={ editable }
			source={ source }
			conditionTypes={ conditionTypes }
			id={ props.id }
			taggable={ props.taggable }
			sortable={ props.sortable }
			disabled={ props.disabled }
		/>
	);
}

Conditions.propTypes = {
	onChange: func,
	value: any,
	default: any,
	required: bool,
	editable: bool,
	disabled: bool,
	readonly: bool,
	sortable: bool,
	taggable: bool,
	conditionTypes: oneOfType( [ object, array ] ),
};
