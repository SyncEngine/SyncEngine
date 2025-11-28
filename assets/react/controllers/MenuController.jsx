import React, { createContext, useContext, useEffect, useState } from 'react';
import { Button, Nav, Navbar, OverlayTrigger, Tooltip } from 'react-bootstrap';
import { logo } from '../components/svg';
import useGlobal from '../hooks/useGlobal';
import useBreakpoint from '../hooks/useBreakpoint';
import useBreadcrumbs from '../hooks/useBreadcrumbs';
import { isArray } from '../utils/conditions';
import { mapSortBy } from '../utils/data';
import Icon from '../components/partials/Icon';

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
	if ( name.includes( 'routine' ) ) {
		return 'routine';
	}
	if ( name.includes( 'step' ) ) { // @todo Remove?
		return 'step';
	}
	if ( name.includes( 'storage' ) ) {
		return 'storage';
	}
}

function getNavStyleOverrides() {
	return {
		'--bs-nav-link-color': 'var(--bs-body-color)',
		'--bs-nav-link-hover-color': 'var(--bs-emphasis-color)',
	};
}

export default function MenuController( props ) {
	const app = useGlobal();
	const main = document.getElementById('main');
	const isMediumDisplay = useBreakpoint( 'md' );
	const [ collapsed, setCollapsed ] = useState( Boolean( ! isMediumDisplay || parseInt( localStorage.getItem( 'menu-collapsed' ), 10 ) ) );
	const [ breadcrumbs ] = useBreadcrumbs();

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
		return ( <Icon icon={ "menu-" + ( collapsed ? "expand" : "collapse" ) } className="fs-5" onClick={ updateCollapsed } /> )
	}

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
			breadcrumbs: breadcrumbs,
			collapsed: collapsed,
			depth: 0,
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
		<div id="menu" className={ props.className + ( ( collapsed ) ? ' collapsed' : ' expanded' ) }>
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
			<div className="overflow-x-hidden overflow-y-auto d-flex flex-column flex-grow-1">
				<Nav className={ 'nav flex-column mb-auto' + ( ( collapsed ) ? '' : ' p-3' ) } style={ getNavStyleOverrides() }>
					{ menu }
				</Nav>
				{ app.hooks.hasOwnProperty( 'sidebar_bottom' ) &&
					<div className={ "align-self-end w-100 p-3 small" + ( collapsed ? ' d-none' : '' ) } dangerouslySetInnerHTML={ { __html: app.hooks.sidebar_bottom } } />
				}
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
rea		}

		return <MenuItem key={ index } { ...item } />
	} )
}

const MenuItem = ( props ) => {
	const context = useContext( MenuContext );

	const {
		baseUrl,
		rootPath,
		currentPath,
		breadcrumbs,
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
	const isCurrent = ( link && rootPath !== link ) ? currentPath.startsWith( link ) || breadcrumbs.map( b => b.link ).includes( link ) : link === currentPath;

	let classes = 'd-flex icon-link icon-link-hover';
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
					{ ( icon && 0 === context.depth )
						?
				        <Icon icon={ icon } className="d-flex fs-5 me-2"></Icon>
						:
						<span className={ 'me-2' } style={ { width: '1.25rem' } }> </span>
					}
					{ ! collapsed &&
					  <span className={ 'menu-collapsible' }>{ title }</span>
					}
				</Nav.Link>
			</OverlayTrigger>
			{ ( ! collapsed && isCurrent && 0 < getItems( name ).length ) && (
				<Nav className={ 'subnav small mb-1 bg-body-tertiary' } style={ getNavStyleOverrides() }>
					<MenuContext.Provider value={ { ...context, depth: context.depth + 1 } }>
						<MenuGroup parent={ name } />
					</MenuContext.Provider>
				</Nav>
			) }
		</Nav.Item>
	)
}
