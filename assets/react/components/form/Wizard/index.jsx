import React, { useState } from 'react';
import { useTranslation } from 'react-i18next';
import { Alert, Card, Nav, Pagination, Stack } from 'react-bootstrap';
import FieldsItem from '../Fields/Item';
import { isEmpty } from '../../../utils/conditions';
import { objectToMappable } from '../../../utils/data';
import Label from '../Label';
import Icon from '../../partials/Icon';

export default function Wizard( props ) {
	const { t } = useTranslation();

	const {
		wizard,
		pages = wizard,
		onChange,
		progress = false, // @todo Progress bar.
		pagination = true, // @todo Pagination location/style.
		wrap = false,
		nav = 'tabs',
		navigation = nav,
	} = props;

	const pagesMap = objectToMappable( pages, 'name' );

	const [ currentPage, setCurrentPage ] = useState( 0 );
	const [ finished, setFinished ] = useState( false );

	if ( ! pages ) {
		return <Alert variant="warning">No pages found.</Alert>
	}

	const callbacks = {
		nextPage: () => { if ( pagesMap[ currentPage + 1 ] ) { setCurrentPage( currentPage + 1 ) } },
		prevPage: () => { if ( pagesMap[ currentPage - 1 ] ) { setCurrentPage( currentPage - 1 ) } },
		firstPage: () => setCurrentPage( 0 ),
		lastPage: () => setCurrentPage( pagesMap.length - 1 ),
	}

	const navigationComponent = navigation && (
		<Nav fill variant={ navigation } activeKey={ pagesMap[ currentPage ].name }>
			{
				pagesMap.map( ( item, index ) => {
					return (
						<Nav.Item key={ item.name }>
							<Nav.Link eventKey={ item.name } onClick={ () => { setCurrentPage( index ) } }>
								<span>{ index + 1 }. </span>
								{ item.icon && <Icon className="mx-1" icon={ item.icon } /> }
								<span>{ item.label ?? item.name }</span>
							</Nav.Link>
						</Nav.Item>
					)
				} )
			}
		</Nav>
	);

	const paginationComponent = pagination && (
		<Pagination className="justify-content-center mb-0">
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

	const page = pagesMap[ currentPage ];
	const title = page.title;
	const description = page.description;
	const content = (
		<FieldsItem key={ page.name } field={ { ...page, label: null, description: null } } wrap={ isEmpty( page.type ) } updateField={ onChange } />
	);

	if ( wrap ) {
		return (
			<Card>
				{ navigation &&
					<Card.Header>
						{ navigationComponent }
					</Card.Header>
				}
				<Card.Body>
					{ title && <Card.Title><Label>{ title }</Label></Card.Title> }
					{ description && <Card.Text>{ description }</Card.Text> }
					{ content }
				</Card.Body>
				{ pagination &&
				    <Card.Footer>
					    { paginationComponent }
				    </Card.Footer>
				}
			</Card>
		)
	}

	return (
		<Stack gap={3}>
			{ navigationComponent }
			<Stack gap={3}>
				{ title && <div className="h3 mb-3">{ title }</div> }
				{ description && <div className="mb-3">{ description }</div> }
				{ content }
			</Stack>
			{ paginationComponent }
		</Stack>
	);
}
