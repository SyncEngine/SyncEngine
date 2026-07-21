import React from 'react';
import PropTypes from 'prop-types';
import { Tab, Tabs } from 'react-bootstrap';
import { deepClone, objectToMappable } from '../../../utils/data';
import { ucfirst } from '../../../utils/globals';
import ResponseTabContent from './Content';

export default function ResponseTabs( props ) {
	return (
		<div className={ props.contained && "tabs-contained h-100" }>
			<Tabs>
				{
					objectToMappable( deepClone( props.data ), 'name', 'content', true ).map( tab => {
						return (
							<Tab eventKey={ tab.name } key={ tab.name } title={ ucfirst( tab.name ) } className={ props.contained && "overflow-hidden w-100" }>
								<ResponseTabContent { ...tab } contained={ props.contained } />
							</Tab>
						)
					} )
				}
			</Tabs>
		</div>
	)
}

ResponseTabs.propTypes = {
	data: PropTypes.object,
	contained: PropTypes.bool,
};
