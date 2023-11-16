import React, { useCallback, useContext, useEffect, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Form, Col, InputGroup } from 'react-bootstrap';
import { TagsContext } from '../../../context/TagsContext';
import Tags from '../../services/Tags';
import { objectToMappable } from "../../../utils/data";
import { isEmpty } from '../../../utils/conditionals';

export default function ColumnsCol( props ) {
	const { t } = useTranslation();

	const {
		value,
		choices = {},
		customizable = isEmpty( choices ),
		taggable = false,
		nest = false,
		onChange,
	} = props;

	const tags = taggable && useContext( TagsContext );

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
		( event ) => { onChange( event.target.value.replace( "\n", '' ) ) },
		[ onChange ]
	);

	// @todo Implement param nesting.

	const field = ( custom || 'object' === typeof value )
		?
		<>
			<Form.Control
				column="text"
				as={ multiline ? "textarea" : undefined }
				placeholder={ props.placeholder ?? null }
				value={ ( 'object' === typeof value ) ? JSON.stringify( value ) : value }
				onChange={ update }
			/>
			{ value.length > 50 &&
				<InputGroup.Text role="button" onClick={ () => { setMultiline( ! multiline ) } } >
					{ multiline ?
						<span className="bi bi-input-cursor" />
						:
						<span className="bi bi-textarea-resize" />
					}
				</InputGroup.Text>
			}
			{ tags &&
				<Tags tags={ tags } callback={ onChange } trigger={ <InputGroup.Text role="button"><span className="bi bi-braces" /></InputGroup.Text> } />
			}
		</>
		:
		<Form.Select
			aria-label=""
			value={ value }
			onChange={ update }
		>
			<option value="">{ props.selectLabel ?? '-- ' + t('Select') + ' --' }</option>
			{
				objectToMappable( choices, 'value', 'label' ).map( ( paramTypeKey, keyIndex ) => {
					return <option key={ keyIndex } value={ paramTypeKey.value }>{ paramTypeKey.label ?? paramTypeKey.value }</option>
				} )
			}
		</Form.Select>;

	const label = custom ? t('Switch to predefined options') :  t('Switch to custom input');

	return (
		<Col>
			<InputGroup>
			{ ( customizable && choices && 'object' !== typeof value ) ?
				<>
					{ field }
					<InputGroup.Text role="button" onClick={ toggleCustom } aria-label={ label } title={ label }>
						{ custom ?
							<span className="bi bi-view-list" />
							:
							<span className="bi bi-input-cursor-text" />
						}
					</InputGroup.Text>
				</>
				:
				field
			}
			</InputGroup>
		</Col>
	);
}
