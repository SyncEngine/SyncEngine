import React, { useEffect, useState } from 'react';
import { Button, Image, Nav, Navbar, OverlayTrigger, Tooltip } from 'react-bootstrap';

const logo = <svg style={{fill: 'var(--bs-body-color)'}} width="32" height="32" viewBox="0 0 3000 3000" version="1.1" xmlns="http://www.w3.org/2000/svg">
	<path shapeRendering="geometricPrecision" d="M2359.7,821.815C2104.05,481.517 1672.01,328.367 1217.32,450.201C752.697,574.695 453.648,935.611 413.028,1370.39L630.907,1312.01L266.885,1943.18C254.727,1909.25 243.866,1874.55 234.372,1839.12C47.205,1140.61 462.357,421.54 1160.88,234.372C1735.7,80.349 2324.43,334.212 2619.22,818.217L2488.1,1045.33L1019.31,1438.89L1335.68,890.574L1393.83,1107.59L2341.28,853.719L2359.7,821.815ZM641.154,2179.64C896.565,2519.05 1327.32,2671.81 1781.39,2550.15C2246.26,2425.58 2546.15,2064.14 2586.86,1628.94L2369.67,1687.14L2733.29,1057.3C2745.38,1091.07 2756.18,1125.61 2765.63,1160.88C2952.8,1859.39 2537.64,2578.46 1839.12,2765.63C1264.45,2919.61 675.87,2665.92 381.005,2182.16L512.429,1954.53L1976.41,1562.24L1661.64,2107.09L1604.08,1892.28L661.2,2144.92L641.154,2179.64Z"/>
</svg>;

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

	const navStyles = {};
	navStyles[ '--bs-nav-link-color' ] = 'var(--bs-body-color)';
	navStyles[ '--bs-nav-link-hover-color' ] = 'var(--bs-emphasis-color)';

	return (
		<div id="menu" className={ 'shadow-lg d-flex overflow-hidden flex-column border-end border-secondary border-opacity-10 bg-body-contrast ' + ( ( collapsed ) ? 'collapsed' : 'expanded' ) }>
			<Navbar className="d-flex justify-content-between px-3">
				{ ! collapsed &&
					<a id="logo" className="navbar-brand menu-collapsible" href="/">{ logo } <b>Sync</b>Engine</a>
				}
				<Button variant="link-secondary" className="btn-sm p-0" type="button" aria-label="Toggle navigation">
					{ getToggleIcon() }
				</Button>
			</Navbar>
			<Nav className={ 'nav flex-column mb-auto' + ( ( collapsed ) ? '' : ' p-3' ) } style={ navStyles }>
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
							variant,// = 'secondary',
						} = item;

						const isCurrent = link === currentPath;

						let classes = 'nav-link d-flex icon-link icon-link-hover';

						if ( variant ) {
							classes += ( isCurrent ) ? ' bg-' + variant + '' : ' bg-hover-' + variant + '-subtle';
							//classes += ' link-hover-' + variant + ( ( isCurrent ) ? ' text-' + variant : '' );
						} else {
							classes += ( isCurrent ) ? ' bg-secondary-subtle text-body-emphasis' : ' bg-hover-secondary-subtle';
						}

						return (
							<Nav.Item key={ index }>
								<OverlayTrigger placement="right" overlay={ <Tooltip placement="right" id={ index }>{ title }</Tooltip> } trigger={ ( collapsed ) ? [ 'hover', 'focus' ] : 'none' }>
									<Nav.Link
										href={ link }
										className={ classes }
										target={ ( link.startsWith( 'http' ) && ! link.startsWith( window.app.baseUrl ) ) ? '_blank' : '' }
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
