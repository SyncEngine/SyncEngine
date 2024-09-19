import React, { useCallback, useContext, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Form, InputGroup } from 'react-bootstrap';

import { TagsContext } from '../../../context/TagsContext';

import Tags from '../../services/Tags';
import SelectGroup from '../Select/SelectGroup';
import SelectOption from '../Select/SelectOption';

import { objectToMappable } from '../../../utils/data';
import { isEmpty, isFieldEditable } from '../../../utils/conditions';
import Icon from '../../partials/Icon';

export default function GridInput( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );

	const {
		value,
		choices = {},
		customizable = isEmpty( choices ),
		taggable = false,
		nest = false,
		onChange,
		onPaste,
	} = props;

	const tagsContext = useContext( TagsContext );
	const tags = ( editable && taggable ) && tagsContext;

	// @todo useMemo?
	const isCustom = () => {
		if ( isEmpty( choices ) ) {
			return true;
		}
		if ( 'object' === typeof value ) {
			return true;
		}
		if ( ! customizable || ! value ) {
			return false;
		}
		if ( Array.isArray( choices ) ) {
			const values = choices.map( choice => choice.value );
			return ! values.includes( value );
		}
		return ! choices.hasOwnProperty( value );
	}

	const [ custom, setCustom ] = useState( isCustom() );
	const [ multiline, setMultiline ] = useState( false );

	useEffect( () => {
		// Set custom on change of choices.
		setCustom( isCustom() );
	}, [ choices ] );

	const toggleCustom = () => customizable && setCustom( ! custom );

	const update = useCallback(
		( event ) => { editable && onChange( event.target.value.replace( "\n", '' ) ) },
		[ onChange, editable ]
	);

	// @todo Implement param nesting.

	const field = ( custom || 'object' === typeof value )
		?
		<>
			<Form.Control
				column="text"
				type={ props.type }
				as={ multiline ? "textarea" : undefined }
				placeholder={ props.placeholder ?? null }
				value={ ( 'object' === typeof value ) ? JSON.stringify( value ) : value }
				onChange={ update }
				onPaste={ onPaste }
				disabled={ props.disabled }
				readOnly={ ! editable }
			/>
			{ value.length > 50 &&
				<InputGroup.Text role="button" onClick={ () => { setMultiline( ! multiline ) } } >
					<Icon icon={ multiline ? "input-text" : "input-textarea" } />
				</InputGroup.Text>
			}
			{ tags &&
				<Tags tags={ tags } callback={ onChange } trigger={ <InputGroup.Text role="button"><Icon icon="tag" /></InputGroup.Text> } />
			}
		</>
		:
		<Form.Select
			aria-label=""
			value={ value }
			onChange={ update }
			onPaste={ onPaste }
			disabled={ props.disabled }
			readOnly={ ! editable }
		>
			<option value="">{ props.selectLabel ?? '-- ' + t('Select') + ' --' }</option>
			{
				objectToMappable( choices, 'value', 'label' ).map( ( option ) => {
					if ( option.choices ) {
						return <SelectGroup key={ option.value } { ...option } />
					}
					return <SelectOption key={ option.value } { ...option } />
				} )
			}
		</Form.Select>;

	const label = custom ? t('Switch to predefined options') :  t('Switch to custom input');

	return (
		<InputGroup>
		{ ( editable && customizable && choices && 'object' !== typeof value ) ?
			<>
				{ field }
				<InputGroup.Text role="button" onClick={ toggleCustom } aria-label={ label } title={ label }>
					<Icon icon={ custom ? "input-select" : "input-text" } />
				</InputGroup.Text>
			</>
			:
			field
		}
		</InputGroup>
	);
}
