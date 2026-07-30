import React, { useState } from 'react';
import PropTypes from 'prop-types';
import { useTranslation } from 'react-i18next';
import { Form, ListGroup } from 'react-bootstrap';
import useEntities from '../../../hooks/useEntities';
import Modal from '../Modal';
import Button from '../../partials/Button';
import Icon from '../../partials/Icon';
import LoadingPlaceholder from '../../partials/Loading/Placeholder';
import { objectToMappable } from '../../../utils/data';
import Info from '../../views/Blocks/Info';

export default function RoutineSelectorModal( props ) {
	const { t } = useTranslation();
	const {
		onSelect,
		onClose,
		entity,
		labels = {}
	} = props;

	const [ entities, entityCallbacks, loading ] = useEntities( entity, {}, {} );
	const [ search, setSearch ] = useState( '' );
	const [ open, setOpen ] = useState( true );

	const handleClose = () => {
		setOpen( false );
		if ( onClose ) onClose();
	};

	const handleSelect = ( entity ) => {
		setOpen( false );
		if ( onSelect ) onSelect( entity );
	};

	const filteredEntities = entities && objectToMappable( entities ).filter( r =>
		!search || ( r.name && r.name.toLowerCase().includes( search.toLowerCase() ) )
	);

	return (
		<Modal show={ open } onHide={ handleClose } centered scrollable size="lg">
			<Modal.Header closeButton>
				<Modal.Title>
					<Icon icon={ entity } className="me-2" />
					{ labels.title ?? t( 'Select {entity}', { entity: entity } ) }
				</Modal.Title>
			</Modal.Header>
			<Modal.Body>
				<Form.Control
					type="text"
					placeholder={ labels.search_placeholder ?? t( 'Search {entity}...', { entity: entity } ) }
					value={ search }
					onChange={ e => setSearch( e.target.value ) }
					className="mb-3"
				/>
				{ loading ? (
					<LoadingPlaceholder />
				) : (
					<ListGroup className="overflow-auto" style={{ maxHeight: '400px' }}>
						{ filteredEntities.length === 0 ? (
							<ListGroup.Item disabled className="text-muted">{ labels.no_results ?? t( 'No results found.' ) }</ListGroup.Item>
						) : (
							filteredEntities.map( item => (
								<ListGroup.Item
									key={ item.id }
									action
									//variant={ entity }
									className="w-100 text-start mb-1"
									onClick={ () => handleSelect( entity ) }
								>
									<Info item={ item } icon={ item.icon } />
								</ListGroup.Item>
							))
						) }
					</ListGroup>
				) }
			</Modal.Body>
			<Modal.Footer>
				<Button variant="secondary" outline onClick={ handleClose }>
					{ t( 'Cancel' ) }
				</Button>
			</Modal.Footer>
		</Modal>
	);
}

RoutineSelectorModal.propTypes = {
	onSelect: PropTypes.func,
	onClose: PropTypes.func,
};
