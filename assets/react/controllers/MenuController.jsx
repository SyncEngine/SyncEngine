import React, { useEffect, useState } from 'react';
import { Button, Nav, Navbar, OverlayTrigger, Tooltip } from 'react-bootstrap';

export default function MenuController( props ) {
	const main = document.getElementById('main');
	const [ collapsed, setCollapsed ] = useState( Boolean( parseInt( localStorage.getItem( 'menu-collapsed' ), 10 ) ) );

	const {
		currentPath,
		items,
	} = props;

	useEffect( () => {
		if ( collapsed ) {
			main.classList.add('menu-collapsed');
			main.classList.remove('menu-expanded');
		} else {
			main.classList.add('menu-expanded');
			main.classList.remove('menu-collapsed');
		}
		if ( ! main.classList.contains( 'menu-loaded' ) ) {
			setTimeout( () => {
				main.classList.add( 'menu-loaded' );
			}, 100 );
		}
	}, [ collapsed ] );

	const updateCollapsed = () => {
		localStorage.setItem( 'menu-collapsed', ( ! collapsed ) ? '1' : '0' );
		setCollapsed( ! collapsed );
	}

	const getToggleIcon = () => {
		if ( collapsed ) {
			return ( <span className="bi bi-text-indent-left fs-5" onClick={ updateCollapsed } /> )
		}
		return ( <span className="bi bi-text-indent-right fs-5" onClick={ updateCollapsed } /> )
	}

	return (
		<div id="menu" className={ 'shadow-lg d-flex overflow-hidden flex-column border-end border-secondary border-opacity-10 bg-body-contrast ' + ( ( collapsed ) ? 'collapsed' : 'expanded' ) }>
			<Navbar className="d-flex justify-content-between px-3">
				{ ! collapsed &&
					<a id="logo" className="navbar-brand menu-collapsible" href="/">KerallConnect</a>
				}
				<Button variant="link-secondary" className="btn-sm p-0" type="button" aria-label="Toggle navigation">
					{ getToggleIcon() }
				</Button>
			</Navbar>
			<Nav className={ 'nav flex-column mb-auto' + ( ( collapsed ) ? '' : ' p-3' ) }>
				{
					items.map( ( item, index ) => {
						if ( 'separator' === item ) {
							return (
								<Nav.Item key={ index }><hr /></Nav.Item>
							);
						}

						const {
							icon,
							title,
							link,
							variant = 'secondary',
						} = item;

						const isCurrent = link === currentPath;

						let classes = 'nav-link d-flex link-body-emphasis icon-link icon-link-hover';

						if ( variant ) {
							classes += ( isCurrent ) ? ' bg-' + variant + '-subtle' : ' bg-hover-' + variant + '-subtle';
						}

						return (
							<Nav.Item key={ index }>
								<OverlayTrigger placement="right" overlay={ <Tooltip placement="right" id={ index }>{ title }</Tooltip> } trigger={ ( collapsed ) ? [ 'hover', 'focus' ] : 'none' }>
									<Nav.Link
										href={ link }
										className={ classes }
									>
										{ icon &&
											    <i className={ "d-flex fs-5 " + icon }></i>
										}
										{ ! collapsed &&
											<span className="ms-2 menu-collapsible">{ title }</span>
										}
									</Nav.Link>
								</OverlayTrigger>
							</Nav.Item>
						)
					} )
				}
			</Nav>
		</div>
	);
}
