import React from 'react';
import { Button, Card, Pagination } from 'react-bootstrap';
import useEntities from '../hooks/useEntities';
import ListView from '../components/views/List';
import TableView from '../components/views/Table';
import EntityModal from '../components/modals/EntityModal';

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

	const [ items, itemsCallbacks ] = useEntities( type, args.items, args.query ?? { limit: 10, offset: 0, total: true } );

	const parseActions = ( actions ) => {
		return actions.map( ( action, index ) => {
			switch ( action ) {

				case 'create':
					return <EntityModal key={ action + index } action="create" type={ type } callback={ itemsCallbacks.create }><Button variant={ type }>Create new</Button></EntityModal>

				case 'total':
					const total = itemsCallbacks.getTotal();
					if ( ! total ) {
						return;
					}
					return <span key={ action + index }>Total: { itemsCallbacks.getTotal() }</span>

				case 'pagination':
					const totalItems = itemsCallbacks.getTotal();
					const query = itemsCallbacks.getQuery();
					if ( totalItems && query.limit && ( totalItems > query.limit ) ) {
						const pages = totalItems / query.limit;
						const current = ( query.offset ) ? parseInt( query.offset / limit ) + 1 : 1;
						return (
							<Pagination key={ action + index }>
								{ ( current > 1 ) &&
									<Pagination.First onClick={ () => { itemsCallbacks.fetch( ( query ) => { query.offset = 0; return query; } ) } } />
								}

								{ ( current < pages ) &&
									<Pagination.Last onClick={ () => { itemsCallbacks.fetch( ( query ) => { query.offset = pages * query.limit; return query; } ) } } />
								}
							</Pagination>
						);
					}
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
							items={ items ?? Array( ( query.limit ?? 1 ) ).fill( 'placeholder' ) }
							type={ type }
						/>
					: <ListView
							callbacks={ itemsCallbacks }
							columns={ columns }
							items={ items ?? Array( ( query.limit ?? 1 ) ).fill( 'placeholder' ) }
							type={ type }
						/>
				}

			</Card.Body>
			{ footer &&
				<Card.Footer>
					{ footer.actions &&
						parseActions( footer.actions )
					}
				</Card.Footer>
			}
		</Card>
	);
}
