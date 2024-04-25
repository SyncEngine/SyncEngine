import React, { createContext, useContext, useEffect, useState } from 'react';
import { Button, Nav, Navbar, OverlayTrigger, Tooltip } from 'react-bootstrap';
import { logo } from '../components/svg';
import useGlobal from '../hooks/useGlobal';
import useBreakpoint from '../hooks/useBreakpoint';
import { isArray } from '../utils/conditions';
import { mapSortBy } from '../utils/data';

const MenuContext = createContext( {} );

function findVariant( name ) {
	if ( name.includes( 'automation' ) ) {
		return 'automation';
	}
	if ( name.includes( 'connection' ) ) {
		return 'connection';
	}
	if ( name.includes( 'flow' ) ) {
		return 'flow';
	}
	if ( name.includes( 'step' ) ) {
		return 'step';
	}
	if ( name.includes( 'storage' ) ) {
		return 'storage';
	}
}

export default function MenuController( props ) {
	const app = useGlobal();
	const main = document.getElementById('main');
	const isMediumDisplay = useBreakpoint( 'md' );
	const [ collapsed, setCollapsed ] = useState( Boolean( ! isMediumDisplay || parseInt( localStorage.getItem( 'menu-collapsed' ), 10 ) ) );

	const {
		currentPath,
		rootPath = '/',
		groups = [],
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

	const navStyles = {};
	navStyles[ '--bs-nav-link-color' ] = 'var(--bs-body-color)';
	navStyles[ '--bs-nav-link-hover-color' ] = 'var(--bs-emphasis-color)';

	const parents = {};
	items.map( ( item ) => {
		if ( ! isArray( parents[ item.parent ] ) ) {
			parents[ item.parent ] = [];
		}
		parents[ item.parent ].push( item );
	} );

	const getItems = ( name ) => {
		return parents[ name ] ?? [];
	}

	const menu = (
		<MenuContext.Provider value={ {
			baseUrl: app.baseUrl,
			rootPath: rootPath,
			currentPath: currentPath,
			collapsed: collapsed,
			getItems: getItems,
		} }>
			{
				groups.map( ( group, _index ) => {

					if ( ! parents[ group ] ) {
						return;
					}

					return (
						<MenuGroup
							key={ group }
							parent={ group }
							separator={ 0 !== _index }
						/>
					);
				} )
			}
		</MenuContext.Provider>
	)

	return (
			<div id="menu" className={ 'shadow-lg d-flex flex-column border-end border-secondary border-opacity-10 bg-body ' + ( ( collapsed ) ? 'collapsed' : 'expanded' ) }>
				<Navbar className={ "d-flex align-items-center justify-content-" + ( collapsed ? 'center flex-column' : 'between px-3' ) } expand={ ! collapsed }>
					<a id="logo" className="navbar-brand menu-collapsible d-flex align-items-center m-0" href="/">
						<span className={ "d-flex" + ( collapsed ? '' : ' me-1' ) }>{ logo }</span>
						{ ! collapsed &&
						  <><b>Sync</b>Engine</>
						}
					</a>
					<Button id="menu-toggle" variant="link-secondary" className="btn-sm p-0" type="button" aria-label="Toggle navigation">
						{ getToggleIcon() }
					</Button>
				</Navbar>
				<div className="overflow-x-hidden overflow-y-auto flex-grow-1">
					<Nav className={ 'nav flex-column mb-auto' + ( ( collapsed ) ? '' : ' p-3' ) } style={ navStyles }>
						{ menu }
					</Nav>
				</div>
			</div>
	);
}

const MenuGroup = ( props ) => {

	const {
		parent,
		separator,
	} = props;

	const context = useContext( MenuContext );

	const items = mapSortBy( context.getItems( parent ), 'position', true );

	if ( separator ) {
		items.unshift( 'separator' );
	}

	return items.map( ( item, index ) => {
		if ( 'separator' === item ) {
			return <Nav.Item key={ parent + index }><hr /></Nav.Item>;
		}

		return <MenuItem key={ index } { ...item } />
	} )
}

const MenuItem = ( props ) => {
	const context = useContext( MenuContext );

	const {
		baseUrl,
		rootPath,
		currentPath,
		collapsed,
		getItems,
	} = context;

	const {
		name,
		icon,
		title,
		link,
		variant = findVariant( name ),// = 'secondary',
		parent,
	} = props;

	// @todo differentiate between current and parent/ancestor.
	const isCurrent = ( link && rootPath !== link ) ? currentPath.startsWith( link ) : link === currentPath;

	let classes = 'nav-link d-flex icon-link icon-link-hover';
	let backgroundClasses = '';

	if ( variant ) {
		backgroundClasses += ( isCurrent ) ? ' bg-' + variant + '' : ' bg-hover-' + variant + '-subtle';
		//backgroundClasses += ' link-hover-' + variant + ( ( isCurrent ) ? ' text-' + variant : '' );
	} else {
		backgroundClasses += ( isCurrent ) ? ' bg-secondary-subtle text-body-emphasis' : ' bg-hover-secondary-subtle';
	}

	classes += backgroundClasses;

	const external = link.startsWith( 'http' ) && ! link.startsWith( baseUrl );

	return (
		<Nav.Item className={ 'flex-grow-1' }>
			<OverlayTrigger
				placement="right"
				overlay={ <Tooltip placement="right" id={ parent + '_' + name + '_tooltip' }>{ title }</Tooltip> }
				trigger={ ( collapsed ) ? [ 'hover', 'focus' ] : 'none' }
			>
				<Nav.Link
					href={ link }
					className={ classes }
					target={ ( external ) ? '_blank' : '' }
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
}
