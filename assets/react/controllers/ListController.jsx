import React from 'react';
import { Button, Card, Form, Stack } from 'react-bootstrap';
import useEntities from '../hooks/useEntities';
import ListView from '../components/views/List';
import TableView from '../components/views/Table';
import EntityModal from '../components/modals/EntityModal';
import Pagination from '../components/views/Pagination';

export default function ListController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const {
		type, // @todo support other types like Modules?
		view = 'table',
		header = {},
		footer = {
			actions: [ 'pagination' ]
		},
		columns = {},
	} = args;

	const queryDefaults = { limit: 10, offset: 0, total: true };

	const [ items, itemsCallbacks ] = useEntities( type, args.items, args.query ? { ...args.query } : { ...queryDefaults } );

	const parseActions = ( actions ) => {
		const query = itemsCallbacks.getQuery();
		const totalItems = itemsCallbacks.getTotal();
		const numPages = parseInt( totalItems / query.limit ) + 1;
		const currentPage = ( query.offset ) ? parseInt( query.offset / query.limit ) + 1 : 1;

		const queryCallbacks = {
			loadMore: () => { itemsCallbacks.fetch( ( query ) => { query.offset = ( query.offset ?? 0 ) + query.limit; return query; }, 'append' ) },
			firstPage: () => { itemsCallbacks.fetch( ( query ) => { query.offset = 0; return query; } ) },
			lastPage: () => { itemsCallbacks.fetch( ( query ) => { query.offset = ( numPages - 1 ) * query.limit; return query; } ) },
			toPage: ( pageNumber ) => { itemsCallbacks.fetch( ( query ) => { query.offset = pageNumber * query.limit; return query; } ) },
			setLimit: ( limit ) => { itemsCallbacks.fetch( ( query ) => { query.limit = limit; return query; } ) }
		}

		return actions.map( ( action, index ) => {
			switch ( action ) {

				case 'create':
					return <EntityModal key={ action + index } action="create" type={ type } callback={ itemsCallbacks.create }><Button variant={ type }>Create new</Button></EntityModal>

				case 'total':
					if ( ! totalItems ) {
						return;
					}
					return <span key={ action + index } className="small text-secondary fw-semibold">
						Showing { items.length } / { totalItems }
						<span className="px-2">|</span>
						Per page:
					    <Form.Select
						    className="ms-1 d-inline-block w-auto"
						    value={ query.limit }
						    size="sm"
						    onChange={ ( e ) => { queryCallbacks.setLimit( e.target.value ) } }
					    >
						    { ( args.query && args.query.limit )
						      ?
							    <>
						        <option value={ parseInt( args.query.limit, 10 ) }>{ args.query.limit }</option>
							    <option value={ args.query.limit * 2 }>{ args.query.limit * 2 }</option>
							    <option value={ args.query.limit * 4 }>{ args.query.limit * 4 }</option>
						        <option value={ args.query.limit * 10 }>{ args.query.limit * 10 }</option>
							    </>
							  :
							    <>
								<option value={ queryDefaults.limit * 2 }>{ queryDefaults.limit * 2 }</option>
							    <option value={ queryDefaults.limit * 2 }>{ queryDefaults.limit * 2 }</option>
						        <option value={ queryDefaults.limit * 4 }>{ queryDefaults.limit * 4 }</option>
						        <option value={ queryDefaults.limit * 10 }>{ queryDefaults.limit * 10 }</option>
							    </>
							}
					    </Form.Select>
					</span>

				case 'loadmore':
					return ( ( items && items.length < totalItems ) &&
						<div key={ action + index } className="pagination pagination-sm loadmore">
							<Button size="sm" className="page-link" onClick={ queryCallbacks.loadMore }>Load more</Button>
						</div>
					)

				case 'pagination':
					return ( ( numPages > 1 ) &&
						<Pagination
							key={ action + index }
							className="m-0"
							size="sm"
							pages={ numPages }
							current={ currentPage }
							callbackFirstPage={ queryCallbacks.firstPage }
							callbackLastPage={ queryCallbacks.lastPage }
							callbackToPage={ queryCallbacks.toPage }
						/>
					);
			}
		});
	}

	return (
		<Card>
			{ header &&
				<Card.Header className={ "d-flex justify-content-between align-items-center" + ( ( type ) ? " bg-" + type + "-subtle" : "" ) }>
					<div className="d-flex align-items-center gap-2">
						{ header.icon &&
							<i className={ "d-flex fs-5 " + header.icon }></i>
						}
						<span>{ header.title }</span>
					</div>
					{ header.actions &&
					    parseActions( header.actions )
					}
				</Card.Header>
			}
			<Card.Body>
				{
					( 'table' === view )
					? <TableView
							callbacks={ itemsCallbacks }
							columns={ columns }
							items={ items ?? Array( ( itemsCallbacks.getQuery( 'limit' ) ?? 10 ) ).fill( 'placeholder' ) }
							type={ type }
						/>
					: <ListView
							callbacks={ itemsCallbacks }
							columns={ columns }
							items={ items ?? Array( ( itemsCallbacks.getQuery( 'limit' ) ?? 10 ) ).fill( 'placeholder' ) }
							type={ type }
						/>
				}

			</Card.Body>
			{ footer &&
				<Card.Footer>
					{ footer.actions &&
						<Stack direction="horizontal" gap={2} className="justify-content-between align-items-center">
							{ parseActions( footer.actions ) }
						</Stack>
					}
				</Card.Footer>
			}
		</Card>
	);
}
