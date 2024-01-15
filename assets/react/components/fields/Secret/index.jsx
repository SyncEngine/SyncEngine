import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Button, InputGroup } from 'react-bootstrap';

import useSecrets from '../../../hooks/useSecrets';
import useToggle from '../../../hooks/useToggle';

import Text from '../Text';
import SelectAdvanced from '../Select/Advanced';

import { getTagParts } from '../../../utils/tags';
import { isEmpty } from '../../../utils/conditions';
import { createRefId } from '../../../utils/globals';

export default function Secret( props ) {
	const { t } = useTranslation();
	const [ secrets, callbacks ] = useSecrets();
	const [ create, toggleCreate, enable, disable ] = useToggle( false );
	const [ name, setKey ] = useState( '' );
	const [ newValue, setNewValue ] = useState( '' );

	const {
		attr = {},
		id = attr.id ?? createRefId(),
		value,
		onChange,
	} = props;

	const parseSecret = useCallback( ( tag ) => {
		if ( ! tag ) {
			return null;
		}

		const parts = getTagParts( tag );

		if ( 'secret' !== parts[0] ) {
			return null;
		}
		return parts[1] ?? null;
	}, [] );

	const handleChangeSecret = useCallback( ( value ) => {
		if ( value ) {
			value = '{{ secret.' + value + ' }}';
		}
		onChange( value );
	}, [ onChange, id, props.name ] );

	const secret = parseSecret( value );

	const [ custom, setCustom ] = useState( ( ! isEmpty( value ) && isEmpty( secret ) ) );

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
				<Text { ...props } type="text" onChange={ onChange } taggable={ true } />
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
							<Text label={ t('Name') } value={ name } onChange={ setKey } />
							<Text label={ t('Value') } value={ newValue } onChange={ setNewValue } />
						</>
						:
						<SelectAdvanced { ...props } onChange={ handleChangeSecret } choices={ secrets } value={ secret } />
					}
				</>
			}
			<InputGroup.Text role="button" onClick={ toggleCustom } aria-label={ customToggleLabel } title={ customToggleLabel }>
				{ custom ?
					<span className="bi bi-key" />
					:
					<span className="bi bi-input-cursor-text" />
				}
			</InputGroup.Text>
		</InputGroup>
	);
}
