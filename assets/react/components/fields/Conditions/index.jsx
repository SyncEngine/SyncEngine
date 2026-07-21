import React, { useEffect, useRef, useState } from 'react';
import { any, array, bool, func, object, oneOfType } from 'prop-types';
import { useTranslation } from 'react-i18next';

import ConditionsGroups from './ConditionsGroups';
import { isFieldEditable } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';

export function parseConditionsValue( value ) {
	if ( ! value ) {
		return {
			conditions: [ { _ref: createRefId(), operator: 'AND', conditions: [] } ],
			operator: 'AND',
		};
	}

	if ( Array.isArray( value ) ) {
		return {
			conditions: [ { _ref: createRefId(), operator: 'AND', conditions: value } ],
			operator: 'AND',
		};
	}

	if ( ! value.conditions ) {
		value.conditions = [];
	}
	if ( ! value.operator ) {
		value.operator = 'AND';
	}

	value.conditions = value.conditions.map( ( group ) => ( {
		_ref: group._ref || createRefId(),
		_label: group._label || '',
		operator: group.operator || 'AND',
		conditions: group.conditions || [],
	} ) );

	if ( ! value.conditions.length ) {
		value.conditions.push( { _ref: createRefId(), operator: 'AND', conditions: [] } );
	}

	return value;
}

export function transformConditionsOutput( data ) {
	if ( 1 === data.conditions.length && 'AND' === data.conditions[0].operator ) {
		return data.conditions[0].conditions;
	}

	return {
		conditions: data.conditions.map( ( g ) => ( {
			_label: g._label || '',
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

	const [ data, setData ] = useState( () => parseConditionsValue( value ) );

	useEffect( () => {
		if ( localUpdateRef.current ) {
			localUpdateRef.current = false;
			return;
		}
		const parsed = parseConditionsValue( value );
		if ( value !== upstreamValueRef.current ) {
			setData( parsed );
			upstreamValueRef.current = value;
		}
	}, [ value ] );

	const updateValue = ( newData ) => {
		const output = transformConditionsOutput( newData );
		localUpdateRef.current = true;
		upstreamValueRef.current = value;
		onChange( output );
		setData( newData );
	};

	return (
		<ConditionsGroups
			label={ props.label }
			groups={ data.conditions }
			operator={ data.operator }
			onGroupsChange={ ( newGroups ) => updateValue( { ...data, conditions: newGroups } ) }
			onOperatorChange={ ( newOperator ) => updateValue( { ...data, operator: newOperator } ) }
			onConditionsChange={ ( index, newConditions ) => {
				const newGroups = [ ...data.conditions ];
				newGroups[ index ] = { ...newGroups[ index ], conditions: newConditions };
				updateValue( { ...data, conditions: newGroups } );
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
