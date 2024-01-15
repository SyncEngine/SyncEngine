import React, { useCallback, useState } from 'react';
import { useTranslation } from 'react-i18next';
import { InputGroup } from 'react-bootstrap';

import Text from '../Text';
import { getTagParts } from '../../../utils/tags';
import { isEmpty } from '../../../utils/conditions';
import SelectAdvanced from '../Select/Advanced';
import { createRefId } from '../../../utils/globals';
import useGlobal from '../../../hooks/useGlobal';
import useSecrets from '../../../hooks/useSecrets';

export default function Secret( props ) {
	const { t } = useTranslation();
	const app = useGlobal();
	const [ secrets ] = useSecrets();

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
		onChange( '{{ secret.' + value + ' }}' );
	}, [ onChange, id, props.name ] );

	const secret = parseSecret( value );

	const [ custom, setCustom ] = useState( ( ! isEmpty( value ) && isEmpty( secret ) ) );

	const toggleCustom = useCallback( () => setCustom( ! custom ), [ custom ] );

	const customToggleLabel = custom ? t('Switch to secret') :  t('Switch to custom input');

	return (
		<InputGroup>
			{ custom ?
				<Text { ...props } type="text" onChange={ onChange } taggable={ true } />
				:
				<SelectAdvanced { ...props } onChange={ handleChangeSecret } choices={ secrets } />
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
