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
	} = props;

	const pagesMap = objectToMappable( pages, 'name' );

	const [ currentPage, setCurrentPage ] = useState( 0 );
	const [ finished, setFinished ] = useState( false );

	const navigationComponent = (
		<Nav fill variant="tabs" defaultActiveKey={ currentPage }>
			{
				pagesMap.each( ( item, index ) => {
					return (
						<Nav.Item key={ index }>
							<Nav.Link onClick={ () => { setCurrentPage( index ) } }>
								{ ( index + 1 ) + '. ' + item.name }
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
				<Pagination.Prev onClick={ () => setCurrentPage( currentPage - 1 ) } />
			}
			{ currentPage < pagesMap ?
				<Pagination.Next onClick={ () => setCurrentPage( currentPage + 1 ) } />
				:
				<Pagination.Item onClick={ () => { setFinished( true ) } }>{ t('Finish') }</Pagination.Item>
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
