import React, { useEffect, useState } from 'react';
import { Button, Nav, Navbar, OverlayTrigger, Tooltip } from 'react-bootstrap';
import { BsArrowBarLeft, BsArrowBarRight } from 'react-icons/bs';

export default function MenuController( props ) {
	const main = document.getElementById('main');
	const [ collapsed, setCollapsed ] = useState( localStorage.getItem( 'menu-collapsed' ) );

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
	}, [ collapsed ] );

	const updateCollapsed = () => {
		localStorage.setItem( 'menu-collapsed', ! collapsed );
		setCollapsed( ! collapsed );
	}

	const getToggleIcon = () => {
		if ( collapsed ) {
			return ( <BsArrowBarRight className="fs-4" onClick={ updateCollapsed }></BsArrowBarRight> )
		}
		return ( <BsArrowBarLeft className="fs-4" onClick={ updateCollapsed }></BsArrowBarLeft> )
	}

	return (
		<div id="menu" className={ "shadow d-flex overflow-hidden flex-column bg-body-tertiary " + ( ( collapsed ) ? 'collapsed' : 'expanded' ) }>
			<Navbar className="bg-body-tertiary d-flex justify-content-between px-3">
				{ ! collapsed &&
					<a id="logo" className="p-0 navbar-brand menu-collapsible" href="/">KerallConnect</a>
				}
				<Button variant="link" className="btn-sm p-0" type="button" aria-label="Toggle navigation">
					{ getToggleIcon() }
				</Button>
			</Navbar>
			<Nav className="nav nav-pills flex-column mb-auto">
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
						} = item;

						const isCurrent = link === currentPath;
						return (
							<Nav.Item key={ index }>
								<OverlayTrigger placement="right" overlay={ <Tooltip placement="right" id={ index }>{ title }</Tooltip> } trigger={ ( collapsed ) ? 'hover' : '' }>
									<Nav.Link
										href={ link }
										className={ "nav-link d-flex icon-link icon-link-hover" + ( ( isCurrent ) ? " active" : '' ) }
									>
										{ icon &&
											    <i className={ "d-flex fs-4 " + icon }></i>
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
