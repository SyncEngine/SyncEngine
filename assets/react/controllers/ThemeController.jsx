import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Dropdown } from 'react-bootstrap';
import { publish } from '../utils/events';
import useGlobal from '../hooks/useGlobal';
import Icon from '../components/partials/Icon';

export default function ThemeController( props ) {
	const { t } = useTranslation();
	const app = useGlobal();
	const [ theme, setTheme ] = useState( app.theme.getStoredTheme() );

	const updateTheme = ( theme ) => {
		app.theme.updateTheme( theme );
		publish( 'updateTheme' );
		setTheme( theme );
	}

	const getIcon = ( theme, props ) => {
		switch ( theme ) {
			default:
			case 'auto':
				return ( <span { ...props }><Icon icon="theme-auto" /></span> )
			case 'light':
				return ( <span { ...props }><Icon icon="theme-light" /></span> )
			case 'dark':
				return ( <span { ...props }><Icon icon="theme-dark" /></span> )
		}
	}

	const themes = [
		{
			'key': 'auto',
			'label': t('Auto'),
		},
		{
			'key': 'light',
			'label': t('Light'),
		},
		{
			'key': 'dark',
			'label': t('Dark'),
		},
	];

	return (
		<Dropdown>
			<Dropdown.Toggle variant="" className={ 'd-flex align-items-center shadow-none border-0' }>
				{ getIcon( theme ) }
			</Dropdown.Toggle>

			<Dropdown.Menu>
				{
					themes.map( ( field, index ) => {
						const {
							key,
							label
						} = field;

						return (
							<Dropdown.Item
								className={ 'd-flex align-items-center' }
								key={ index }
								active={ theme === key }
								onClick={ () => { updateTheme( key ) } }
							>
								{ getIcon( key, { className: 'me-2' } ) } { label }
							</Dropdown.Item>
						)
					} )
				}
			</Dropdown.Menu>
		</Dropdown>
	);
}
