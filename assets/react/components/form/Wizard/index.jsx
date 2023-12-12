import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Card, Nav, Pagination, Stack } from 'react-bootstrap';
import Fields from '../Fields';
import { objectMerge, objectToMappable } from '../../../utils/data';

export default function Wizard( props ) {
	const { t } = useTranslation();

	const {
		pages,
		values,
		onChange,
		progress = false, // @todo Progress bar.
		pagination = true, // @todo Pagination location/style.
		wrap = false,
		nav = 'tabs',
	} = props;

	const pagesMap = objectToMappable( pages, 'name' );

	const [ currentPage, setCurrentPage ] = useState( 0 );
	const [ finished, setFinished ] = useState( false );

	const callbacks = {
		nextPage: () => { if ( pagesMap[ currentPage + 1 ] ) { setCurrentPage( currentPage + 1 ) } },
		prevPage: () => { if ( pagesMap[ currentPage - 1 ] ) { setCurrentPage( currentPage - 1 ) } },
		firstPage: () => setCurrentPage( 0 ),
		lastPage: () => setCurrentPage( pagesMap.length - 1 ),
	}

	const navigationComponent = (
		<Nav fill variant={ nav } defaultActiveKey={ pagesMap[ currentPage ].name }>
			{
				pagesMap.map( ( item, index ) => {
					return (
						<Nav.Item key={ item.name }>
							<Nav.Link eventKey={ item.name } onClick={ () => { setCurrentPage( index ) } }>
								{ ( index + 1 ) + '. ' + ( item.label ?? item.name ) }
							</Nav.Link>
						</Nav.Item>
					)
				} )
			}
		</Nav>
	);

	const paginationComponent = (
		<Pagination>
			{ 0 < currentPage &&
				<Pagination.Prev onClick={ callbacks.prevPage } />
			}
			{ currentPage < ( pagesMap.length - 1 ) ?
				<Pagination.Next onClick={ callbacks.nextPage } />
				:
				<></>//<Pagination.Item onClick={ () => { setFinished( true ) } }>{ t('Finish') }</Pagination.Item>
			}
		</Pagination>
	);

	const title = pagesMap[ currentPage ].title;
	const description = pagesMap[ currentPage ].description;
	const content = pagesMap[ currentPage ].fields && <Fields fields={ pagesMap[ currentPage ].fields } values={ values } onChange={ onChange } />;

	if ( wrap ) {
		return (
			<Card>
				<Card.Header>
					{ navigationComponent }
				</Card.Header>
				<Card.Body>
					{ title && <Card.Title>{ title }</Card.Title> }
					{ description && <Card.Text>{ description }</Card.Text> }
					{ content }
				</Card.Body>
				<Card.Footer>
					{ paginationComponent }
				</Card.Footer>
			</Card>
		)
	}

	return (
		<Stack gap={3}>
			{ navigationComponent }
			<Stack gap={3}>
				{ title && <div className="h3 mb-3">{ pagesMap[ currentPage ].title }</div> }
				{ description && <div className="mb-3">{ pagesMap[ currentPage ].description }</div> }
				{ content }
			</Stack>
			{ paginationComponent }
		</Stack>
	);
}
