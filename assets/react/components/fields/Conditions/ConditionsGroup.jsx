import React, { useMemo } from 'react';
import { string, array, bool, func, number, object, oneOfType } from 'prop-types';
import { useTranslation } from 'react-i18next';

import Grid from '../Grid';
import { getOperators, isFieldEditable } from '../../../utils/conditions';
import GroupToolbar from './GroupToolbar';
import FieldContainer from '../../form/Field/Container';
import useClipboard from '../../../hooks/useClipboard';
import { suppress } from '../../../utils/events';
import { HStack } from '../../partials/Stack';


export default function ConditionsGroup( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );
	const [ clipboard, updateClipboard, clipboardAccessible ] = useClipboard( '', '', false );
	const operators = getOperators();

	const {
		group,
		groupIndex,
		totalGroups,
		onGroupChange,
		onConditionsChange,
		source = {},
		conditionTypes = {},
	} = props;

	const canMoveUp = 0 < groupIndex;
	const canMoveDown = groupIndex < totalGroups - 1;

	const columns = useMemo( () => ( {
		source: {
			header: t( 'Source' ),
			placeholder: '{{ data }}',
			...source,
		},
		key: {
			header: t( 'Field' ),
		},
		operator: {
			header: t( 'Operator' ),
			customizable: false,
			choices: operators,
		},
		compare: {
			header: t( 'Compare' ),
			multiple: true,
		},
	} ), [ t, source, operators ] );

	const onCopy = useMemo( () => function( e ) {
		if ( false === clipboardAccessible ) {
			return;
		}

		suppress( e );

		const rows = group.conditions?.map( item => {
			const row = { ...item };

			delete row._ref;

			return row;
		} );

		if ( rows ) {
			updateClipboard( JSON.stringify( rows ) );
		}
	}, [ group.conditions ] );

	const onPaste = useMemo( () => function( e ) {
		const paste = e.clipboardData.getData('Text');

		try {
			const conditions = JSON.parse( paste );
			onConditionsChange( groupIndex, conditions );
		} catch ( error ) {
			console.error( 'Failed to parse clipboard data:', error );
		}
	} );

	const displayLabel = totalGroups > 1 ? ( group._label || ( ( groupIndex + 1 ) + '.' ) ) : undefined;

	const onUpdateLabel = ( newLabel ) => {
		const updated = { ...group, _label: newLabel };
		onGroupChange( groupIndex, updated );
	};

	return ( <>
		<FieldContainer label={ displayLabel } onUpdateLabel={ totalGroups > 1 ? onUpdateLabel : undefined } toolbar={ editable ?
			<GroupToolbar
				index={ groupIndex }
				operator={ group.operator }
				onOperatorChange={ ( op ) => {
					const updated = { ...group, operator: op };
					onGroupChange( groupIndex, updated );
				} }
				canMoveUp={ canMoveUp }
				canMoveDown={ canMoveDown }
				onMoveUp={ () => {
					const groups = props.groups;
					const newGroups = [ ...groups ];
					const temp = newGroups[ groupIndex - 1 ];
					newGroups[ groupIndex - 1 ] = newGroups[ groupIndex ];
					newGroups[ groupIndex ] = temp;
					props.onGroupsChange( newGroups );
				} }
				onMoveDown={ () => {
					const groups = props.groups;
					const newGroups = [ ...groups ];
					const temp = newGroups[ groupIndex + 1 ];
					newGroups[ groupIndex + 1 ] = newGroups[ groupIndex ];
					newGroups[ groupIndex ] = temp;
					props.onGroupsChange( newGroups );
				} }
				onDelete={ () => props.onDeleteGroup( groupIndex ) }
				onCopy={ onCopy }
				editable={ editable }
			/>
		: undefined }>
			<Grid
				id={ props.id }
				editable={ editable }
				taggable={ props.taggable }
				sortable={ props.sortable ?? editable }
				disabled={ props.disabled }
				value={ group.conditions }
				onChange={ ( val ) => onConditionsChange( groupIndex, val ) }
				columns={ columns }
				onCopy={ editable ? false : undefined }
				onPaste={ onPaste }
			/>
		</FieldContainer>

		{ props.showSeparator && (
			<HStack className="justify-content-center my-2 py-1 px-2">
				<span className="text-uppercase small text-secondary fw-semibold mb-0">
					-- { props.combineOperator } --
				</span>
			</HStack>
		) }
	</> )
}

ConditionsGroup.propTypes = {
	group: object,
	groupIndex: number,
	totalGroups: number,
	onGroupChange: func,
	onConditionsChange: func,
	onDeleteGroup: func,
	onGroupsChange: func,
	groups: array,
	editable: bool,
	disabled: bool,
	sortable: bool,
	taggable: bool,
	source: object,
	conditionTypes: oneOfType( [ object, array ] ),
	id: oneOfType( [ string, object ] ),
	showSeparator: bool,
	combineOperator: string,
	onToggleCombine: func,
};
