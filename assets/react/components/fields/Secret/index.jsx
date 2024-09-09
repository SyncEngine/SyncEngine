import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, InputGroup } from 'react-bootstrap';

import useSecrets from '../../../hooks/useSecrets';
import useToggle from '../../../hooks/useToggle';
import Input from '../Input';
import SelectAdvanced from '../Select/Advanced';

import { getTagParts, isTag } from '../../../utils/tags';
import { isEmpty, isFieldEditable } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';
import Icon from '../../partials/Icon';

function parseSecret( tag ) {
	if ( ! tag ) {
		return null;
	}

	const parts = getTagParts( tag );

	if ( 'vault' !== parts[0] ) {
		return null;
	}
	return parts[1] ?? null;
}

export default function Secret( props ) {
	const { t } = useTranslation();
	const [ secrets, callbacks ] = useSecrets();
	const editable = isFieldEditable( props );

	const {
		attr = {},
		id = attr.id ?? createRefId(),
		value,
		customizable = true,
		onChange,
	} = props;

	const secret = parseSecret( value );
	const [ custom, setCustom ] = useState( ( customizable && ! isEmpty( value ) && isEmpty( secret ) ) );
	const [ hidden, toggleHidden ] = useToggle( ! isTag( value ) );

	const [ create, toggleCreate, enable, disable ] = useToggle( false );
	const [ name, setKey ] = useState( '' );
	const [ newValue, setNewValue ] = useState( custom ? value : '' ); // Auto-set current value if exists.

	const handleChangeSecret = useCallback( ( value ) => {
		if ( value ) {
			value = '{{ vault.' + value + ' }}';
		}
		onChange( value );
	}, [ onChange, id, props.name ] );

	const toggleCustom = useCallback( () => setCustom( ! custom ), [ custom ] );

	const customToggleLabel = custom ? t('Switch to secret') :  t('Switch to custom input');

	const handleCreate = async () => {
		const response = await callbacks.add( name, newValue );

		if ( Array.isArray( response ) && response.includes( name ) ) {
			setKey( '' );
			setNewValue( '' );
			handleChangeSecret( name );
			disable();
		}
	}

	return (
		<InputGroup>
			{ custom ?
				<>
					<Input { ...props } type={ ! hidden ? 'text' : 'password' } multiline="auto" onChange={ onChange } taggable={ editable } />
					<InputGroup.Text role="button" onClick={ toggleHidden }>
						<Icon icon={ hidden ? 'eye' : 'eye-slash' } />
					</InputGroup.Text>
				</>
				:
				<>
					{ props.help && <Help id={ id } text={ props.help } inputGroup={ true } /> }
					<InputGroup.Text role="button" onClick={ toggleCreate }>
						<Icon icon={ create ? 'cross' : 'plus' } />
					</InputGroup.Text>
					{ ( editable && create ) ?
						<>
							<Button onClick={ handleCreate } disabled={ ( ! newValue || ! name ) }>
								<Icon icon="check" /> { t('Create') }
							</Button>
							<Input label={ t('Name') } value={ name } onChange={ setKey } />
							<Input label={ t('Value') } value={ newValue } multiline="auto" onChange={ setNewValue } />
						</>
						:
						<SelectAdvanced { ...props } onChange={ handleChangeSecret } choices={ secrets } value={ secret } />
					}
				</>
			}
			{ customizable &&
				<InputGroup.Text role="button" onClick={ toggleCustom } aria-label={ customToggleLabel } title={ customToggleLabel }>
					<Icon icon={ custom ? 'key' : 'input-cursor-text' } />
				</InputGroup.Text>
			}
		</InputGroup>
	);
}
