import React from 'react';
import { Alert, Stack, Tab, Tabs } from 'react-bootstrap';

import FieldsItem from '../Fields/Item';
import { objectToMappable } from '../../../utils/data';
import { isConfigured, isEmpty } from '../../../utils/conditions';
import Icon from '../../partials/Icon';
import useFieldValue from '../../../hooks/useFieldValue';
import Label from '../Label';

function getDefaultTab( defaultTab ) {
	if ( '#' === defaultTab ) {
		return window.location.hash.substring(1) || undefined;
	}
	return defaultTab;
}

export default function TabsControl( props ) {

	const {
		tabs,
		defaultTab,
		onChange,
	} = props;

	if ( ! tabs ) {
		return (
			<Alert variant="warning">No tabs found.</Alert>
		)
	}

	return (
		<Stack gap={0}>
			<Tabs defaultActiveKey={ getDefaultTab( defaultTab ) }>
				{
					objectToMappable( tabs, 'name' ).map( ( tab, index ) => {
						let {
							name,
							label,
							indicator,
						} = tab;

						const [ fieldValue ] = useFieldValue( name );

						label = <Label>{ label }</Label>;

						if ( indicator && isConfigured( fieldValue, tab.default ) ) {
							label = (
								<span className="d-block position-relative">
									{ label }
									<Icon icon="gear-fill" className="position-absolute top-0 end-0 me-n2 mt-n2 fs-smaller" />
								</span>
							);
						}

						return (
							<Tab key={ name } eventKey={ name } title={ label } className="p-3 border bg-body">
								{ tab.description &&
									<p className="form-text text-muted">{ tab.description }</p>
								}
								<FieldsItem field={ { ...tab, label: null, description: null } } wrap={ isEmpty( tab.type ) } updateField={ onChange } />
							</Tab>
						)
					} )
				}
			</Tabs>
		</Stack>
	);
}
