import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Alert, Button, ButtonGroup } from 'react-bootstrap';

import Grid from '../Grid';
import Code from '../Code';
import Mapper from '../Mapper';
import Repeater from '../Repeater';
import { deepClone, objectToMappable } from '../../../utils/data';
import Icon from '../../partials/Icon';
import { isFieldEditable } from '../../../utils/conditions';

export default function Dataset( props ) {
	const { t } = useTranslation();
	const editable = isFieldEditable( props );

	const {
		value = [],
		type,
		onChange,
		storageConfig = {},
		columns = storageConfig.columns ?? [],
	} = props;

	const [ storage, setStorage ] = useState( value );
	const [ error, setError ] = useState( '' );
	const [ view, setView ] = useState( ( ( Array.isArray( columns ) && columns.length ) || 'mapper' === type ) ? 'grid' : 'code' );

	const updateStorage = ( newStorage ) => {
		setStorage( newStorage );
		onChange( newStorage );
	}

	const updateInput = ( value ) => {
		try {
			const newStorage = JSON.parse( value );
			updateStorage( newStorage );
			setError( '' );
		} catch ( e ) {
			updateStorage( value );
			setError( t('Cannot parse JSON') );
		}
	}

	let control = [];

	switch ( view ) {
		case 'grid':
			if ( 'mapper' === type ) {
				control = (
					<Mapper
						values={ storageConfig.mapper ?? {} }
						value={ deepClone( storage ) }
						choices="schema"
						onChange={ updateStorage }
						editable={ editable }
						taggable={ props.taggable }
						disabled={ props.disabled }
					/>
				);
			} else if ( 'fields' === type ) {
				if ( storageConfig.fields && 'advanced' === storageConfig.fields.configuration ) {
					control = (
						<Repeater
							fields={ storageConfig.fields.fieldset ?? {} }
							value={ deepClone( storage ) }
							onChange={ updateStorage }
							editable={ editable }
							disabled={ props.disabled }
						/>
					);
				} else {
					control = (
						<Grid
							value={ objectToMappable( deepClone( storage ), 'key', 'label' ) }
							onChange={ updateStorage }
							columns={ {
								key: t('Field Key'),
								label: t('Field Label'),
							} }
							editable={ editable }
							taggable={ props.taggable }
							disabled={ props.disabled }
						/>
					);
				}
			} else {
				control = (
					<Grid
						value={ deepClone( storage ) }
						onChange={ updateStorage }
						columns={ columns }
						editable={ editable }
						taggable={ props.taggable }
						disabled={ props.disabled }
					/>
				);
			}
			break;
		default:
			control = (
				<Code
					height="60vh"
					value={ ( 'object' === typeof storage ) ? JSON.stringify( storage, null, 4 ) : storage }
					onChange={ updateInput }
					editable={ editable }
					taggable={ props.taggable }
					disabled={ props.disabled }
				/>
			);
			break;
	}

	return (
		<div className="p-3 border bg-body">
			{ error &&
				<Alert variant="warning">{ error }</Alert>
			}

			{ columns &&
			    <ButtonGroup>
					<Button variant={ ( 'code' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'code' ) } }><Icon icon="code" /></Button>
					<Button variant={ ( 'grid' === view ) ? 'secondary' : 'outline-secondary' } onClick={ () => { setView( 'grid' ) } }><Icon icon="grid" /></Button>
			    </ButtonGroup>
			}

			{ control }
		</div>
	);
}
