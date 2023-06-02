import React, { useState } from 'react';
import { Dropdown } from "react-bootstrap";
import { BsSunFill, BsMoonStarsFill, BsCircleHalf, BsSubtract } from "react-icons/bs";

export default function ThemeController( props ) {
	const [ theme, setTheme ] = useState( window.bootstrap.getStoredTheme() );

	const updateTheme = ( theme ) => {
		window.bootstrap.updateTheme( theme );
		setTheme( theme );
	}

	const getIcon = ( theme, props ) => {
		switch ( theme ) {
			default:
			case 'auto':
				return ( <BsCircleHalf { ...props }></BsCircleHalf> )
			case 'light':
				return ( <BsSunFill { ...props }></BsSunFill> )
			case 'dark':
				return ( <BsMoonStarsFill { ...props }></BsMoonStarsFill> )
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
			<Dropdown.Toggle variant="" className={ 'd-flex align-items-center shadow-none border-0' }>{ getIcon( theme ) }</Dropdown.Toggle>

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
