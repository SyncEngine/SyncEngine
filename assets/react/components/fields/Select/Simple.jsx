import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { FloatingLabel, Form, InputGroup } from 'react-bootstrap';

import Help from '../../form/Help';
import Description from '../../form/Description';
import SelectGroup from './SelectGroup';
import SelectOption from './SelectOption';

import { objectToMappable } from '../../../utils/data';
import { isEmpty, isFieldEditable } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';
import Icon from '../../partials/Icon';

export default function SelectSimple( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );

	const {
		label,
		attr = {},
		id = attr.id ?? createRefId(),
		customizable = false,
		onChange,
		prefix,
		postfix,
	} = props;

	const value = props.value ?? props.default ?? '';

	// @todo deprecate options support?
	const choices = objectToMappable( props.choices ?? props.options ?? {}, 'value', 'label' );

	// @todo useMemo?
	const isCustom = () => {
		if ( ! customizable || ! value ) {
			return false;
		}
		if ( isEmpty( choices ) ) {
			return true;
		}
		if ( 'object' === typeof value ) {
			return false;
		}
		if ( Array.isArray( choices ) ) {
			const values = choices.map( choice => choice.value );
			return ! values.includes( value );
		}
		return ! choices.hasOwnProperty( value );
	}

	const [ custom, setCustom ] = useState( isCustom() );

	const toggleCustom = () => ( editable && customizable ) && setCustom( ! custom );

	const handleChange = useCallback( ( e ) => {
		if ( ! editable ) {
			return;
		}

		onChange( e.target.value );
	}, [ onChange, id, props.name, editable ] );

	const customToggleLabel = custom ? t('Switch to predefined options') :  t('Switch to custom input');

	const control = custom ?
		<Form.Control
			{ ...attr }
			column="text"
			label={ label }
			required={ props.required ?? attr.required }
			placeholder={ props.placeholder ?? attr.placeholder ?? '' }
			value={ value }
			onChange={ handleChange }
			disabled={ props.disabled ?? attr.disabled }
			readOnly={ props.readOnly ?? attr.readOnly ?? props.readonly ?? attr.readonly }
		/>
		:
		<Form.Select
			{ ...attr }
			label={ label }
			required={ props.required ?? attr.required }
			placeholder={ props.placeholder ?? attr.placeholder ?? props.label }
			value={ value }
			onChange={ handleChange }
			disabled={ props.disabled ?? attr.disabled }
			readOnly={ props.readOnly ?? attr.readOnly ?? props.readonly ?? attr.readonly }
		>
			{ ( choices[0] && ! isEmpty( choices[0].value ) ) &&
			  <option value="">{ props.selectLabel ?? '-- ' + t('Select') + ' --' }</option>
			}
			{
				choices.map( ( option, index ) => {
					if ( option.choices ) {
						return <SelectGroup key={ option.value } { ...option } />
					}
					return <SelectOption key={ option.value } { ...option } />
				} )
			}
		</Form.Select>;

	return (
		<div className="flex-grow-1">
			<InputGroup>
				{ props.help &&
					<Help id={ id } text={ props.help } inputGroup={ true } />
				}
				{ prefix &&
					<InputGroup.Text>{ prefix }</InputGroup.Text>
				}
				{ label ?
					<FloatingLabel label={ label }>{ control }</FloatingLabel>
					:
					control
				}
				{ postfix &&
					<InputGroup.Text>{ postfix }</InputGroup.Text>
				}
				{ ( editable && customizable ) &&
					<InputGroup.Text role="button" onClick={ toggleCustom } aria-label={ customToggleLabel } title={ customToggleLabel }>
						<Icon icon={ custom ? "input-select" : "input-text" } />
					</InputGroup.Text>
				}
			</InputGroup>
			{ props.description &&
				<Description text={ props.description } id={ id } />
			}
		</div>
	);
}
