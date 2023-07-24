import React from 'react';
import List from '../components/views/List';
import useEntities from '../hooks/useEntities';
import { Button, Card, Pagination } from 'react-bootstrap';
import EntityModal from '../components/modals/EntityModal';

export default function ListController( props ) {

	const {
		args = {},
		onChange,
	} = props;

	const {
		type, // @todo support other types like Modules?
		header = {},
		footer = {
			actions: [ 'pagination' ]
		},
		columns = {},
	} = args;

	const [ items, itemsCallbacks, total ] = useEntities( type, args.items, args.query ?? { limit: 10, offset: 0, total: true } );

	const parseActions = ( actions ) => {
		return actions.map( ( action ) => {
			switch ( action ) {
				case 'create':
					return <EntityModal key={ action } action="create" type={ type } callback={ itemsCallbacks.create }><Button variant={ type }>Create new</Button></EntityModal>
				case 'pagination':
					return <Pagination key={ action }  />
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
				<List
					callbacks={ itemsCallbacks }
					columns={ columns }
					items={ items ?? Array( ( query.limit ?? 1 ) ).fill( 'placeholder' ) }
					type={ type }
				/>
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
