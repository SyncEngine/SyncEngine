import React, { useEffect } from 'react';
import { useTranslation } from 'react-i18next';
import { Card, Stack } from 'react-bootstrap';

import Button from '../components/partials/Button';
import useGlobal from '../hooks/useGlobal';
import useEntities from '../hooks/useEntities';
import usePreference from '../hooks/usePreference';

import EntityModal from '../components/modals/EntityModal';
import ListView from '../components/views/List';
import TableView from '../components/views/Table';
import Pager from '../components/partials/PaginationToolbar/Pager';
import LoadMore from '../components/partials/PaginationToolbar/LoadMore';
import PaginationInfo from '../components/partials/PaginationToolbar/Info';
import Icon from '../components/partials/Icon';

export default function ListController( props ) {
	const { t } = useTranslation();
	const app = useGlobal();

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

	const disableQuery = false === args.query;

	const [ preferredLimit, setPreferredLimit, hasPreferredLimit ] = usePreference( type + '_list_limit', 'view', args.query ? args.query.limit ?? 10 : 10 );

	const queryDefaults = disableQuery ? {} : { limit: 10, offset: 0, total: true };

	const [ items, itemsCallbacks, loading ] = useEntities( type, args.items, ! disableQuery ? args.query ? { ...args.query, limit: preferredLimit } : { ...queryDefaults, limit: preferredLimit } : null );

	const query = itemsCallbacks.getQuery() ?? {};
	const totalItems = itemsCallbacks.getTotal() ?? args.total ?? 0;
	const numPages = parseInt( ( totalItems - 1 ) / query.limit ) + 1;
	const currentPage = ( query.offset ) ? parseInt( query.offset / query.limit ) + 1 : 1;

	const queryCallbacks = {
		loadMore: () => { itemsCallbacks.fetch( ( query ) => { query.offset = ( query.offset ?? 0 ) + query.limit; return query; }, 'append' ) },
		firstPage: () => { itemsCallbacks.fetch( ( query ) => { query.offset = 0; return query; } ) },
		lastPage: () => { itemsCallbacks.fetch( ( query ) => { query.offset = ( numPages - 1 ) * query.limit; return query; } ) },
		toPage: ( pageNumber, pageIndex ) => { itemsCallbacks.fetch( ( query ) => { query.offset = pageIndex * query.limit; return query; } ) },
		setLimit: ( limit ) => { itemsCallbacks.fetch( ( query ) => { setPreferredLimit( limit ); query.limit = limit; return query; } ) }
	}

	/**
	 * Fetch items based on preferences.
	 */
	useEffect( () => {
		if ( ! disableQuery && ! loading && hasPreferredLimit && ( ! items || ( items.length < preferredLimit && items.length < totalItems ) ) ) {
			itemsCallbacks.fetch( () => { query.limit = preferredLimit; return query }, 'silent' );
		}
	}, [ preferredLimit ] );

	const parseActions = ( actions ) => {
		return actions.map( ( action, index ) => {
			switch ( action ) {

				case 'create':
					return (
						<EntityModal
							key={ action + index }
							action="create"
							type={ type }
							callback={ itemsCallbacks.add }
							editCallback={ itemsCallbacks.edit }
							savable={ true }
						>
							<Button variant={ type } subtle>
								<Icon className="me-2" icon={ app.icons.hasOwnProperty( type ) ? app.icons[ type ] : 'plus' } />
								{ t('Create new') }
							</Button>
						</EntityModal>
					);

				case 'total':
					if ( ! totalItems ) {
						return;
					}
					return (
						<PaginationInfo
							size="sm"
							key={ action + index }
							callbackSetLimit={ queryCallbacks.setLimit }
							limit={ ( query && query.hasOwnProperty( 'limit' ) ) ? query.limit : hasPreferredLimit ? preferredLimit : queryDefaults.limit }
							offset={ query.offset }
							defaultLimit={ queryDefaults.limit }
							numItems={ items && items.length }
							totalItems={ totalItems }
						/>
					)

				case 'loadmore':
					return (
						<LoadMore
							size="sm"
							key={ action + index }
							loadedItems={ items && items.length }
							totalItems={ totalItems }
							callback={ queryCallbacks.loadMore }
						/>
					)

				case 'pagination':
					return ( ( numPages > 1 ) &&
						<Pager
							key={ action + index }
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
				<Card.Header className={ "d-flex justify-content-between align-items-center" + ( ( type ) ? " text-" + type + "-emphasis border-" + type : "" ) }>
					<div className="d-flex align-items-center gap-2 h5 m-0">
						{ header.icon &&
							<i className={ "d-flex " + header.icon }></i>
						}
						<span>{ header.title }</span>
					</div>
					{ header.actions &&
					    parseActions( header.actions )
					}
				</Card.Header>
			}
			<Card.Body className={ loading && "opacity-50" }>
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
				<Card.Footer className="py-2 sticky-bottom bg-body-tertiary bg-opacity-50 backdrop-filter-blur-5">
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
