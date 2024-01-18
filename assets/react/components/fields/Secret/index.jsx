import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, InputGroup } from 'react-bootstrap';

import useSecrets from '../../../hooks/useSecrets';
import useToggle from '../../../hooks/useToggle';
import Input from '../Input';
import SelectAdvanced from '../Select/Advanced';

import { getTagParts, isTag } from '../../../utils/tags';
import { isEmpty } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';

export default function Secret( props ) {
	const { t } = useTranslation();
	const [ secrets, callbacks ] = useSecrets();

	const {
		attr = {},
		id = attr.id ?? createRefId(),
		value,
		customizable = true,
		onChange,
	} = props;

	const parseSecret = useCallback( ( tag ) => {
		if ( ! tag ) {
			return null;
		}

		const parts = getTagParts( tag );

		if ( 'vault' !== parts[0] ) {
			return null;
		}
		return parts[1] ?? null;
	}, [] );

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
					<Input { ...props } type={ ! hidden ? 'text' : 'password' } onChange={ onChange } taggable={ true } />
					<InputGroup.Text role="button" onClick={ toggleHidden }>
						{ hidden ?
							<span className="bi bi-eye" />
							:
							<span className="bi bi-eye-slash" />
						}
					</InputGroup.Text>
				</>
				:
				<>
					<InputGroup.Text role="button" onClick={ toggleCreate }>
						<span className={ "bi bi-" + ( create ? 'x-lg' : 'plus-lg' ) } />
					</InputGroup.Text>
					{ create ?
						<>
							<Button onClick={ handleCreate } disabled={ ( ! newValue || ! name ) }>
								<span className="bi bi-check-lg" /> { t('Create') }
							</Button>
							<Input label={ t('Name') } value={ name } onChange={ setKey } />
							<Input label={ t('Value') } value={ newValue } onChange={ setNewValue } />
						</>
						:
						<SelectAdvanced { ...props } onChange={ handleChangeSecret } choices={ secrets } value={ secret } />
					}
				</>
			}
			{ customizable &&
				<InputGroup.Text role="button" onClick={ toggleCustom } aria-label={ customToggleLabel } title={ customToggleLabel }>
					{ custom ?
						<span className="bi bi-key" />
						:
						<span className="bi bi-input-cursor-text" />
					}
				</InputGroup.Text>
			}
		</InputGroup>
	);
}
