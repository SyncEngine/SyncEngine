import React from 'react';
import { ListGroup, Tab, Tabs } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import TagsItem from './Item';
import TagsItemStorage from './Storage';
import Icon from '../../partials/Icon';
import { useTranslation } from 'react-i18next';
import { ucfirst } from '../../../utils/globals';

export default function TagsTabs( props ) {
	const { t } = useTranslation();

	const {
		tags,
	} = props;

	return (
		<Tabs>
			{
				objectToMappable( { ...tags }, 'tag', 'children', true ).map( ( item, index ) => {
					let icon  = item.icon ?? item.tag;
					let title = t( item.title ?? ucfirst( item.tag ) );
					let component = <TagsItem key={ index } { ...props } tag={ item.tag } children={ item.children } open={ true } />;

					if ( '_storage' === item.tag ) {
						title = t( 'Storage' );
						icon = 'storage';
						component = <TagsItemStorage key={ index } { ...props } tag="storage" />
					}

					return <Tab eventKey={ item.tag } title={ <span><Icon icon={ icon } /> { title }</span> } >{ component }</Tab>
				} )
			}
		</Tabs>
	);
}
