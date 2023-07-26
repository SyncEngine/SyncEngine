import React, { useState } from 'react';
import { Dropdown } from "react-bootstrap";
import { publish } from '../utils/events';

export default function ThemeController( props ) {
	const [ theme, setTheme ] = useState( window.bootstrap.getStoredTheme() );

	const updateTheme = ( theme ) => {
		window.bootstrap.updateTheme( theme );
		publish( 'updateTheme' );
		setTheme( theme );
	}

	const getIcon = ( theme, props ) => {
		switch ( theme ) {
			default:
			case 'auto':
				return ( <span { ...props }><span className="bi bi-circle-half" /></span> )
			case 'light':
				return ( <span { ...props }><span className="bi bi-sun-fill" /></span> )
			case 'dark':
				return ( <span { ...props }><span className="bi bi-moon-stars-fill" /></span> )
		}
	}

	const themes = [
		{
			'key': 'auto',
			'label': 'Auto',
		},
		{
			'key': 'light',
			'label': 'Light',
		},
		{
			'key': 'dark',
			'label': 'Dark',
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
