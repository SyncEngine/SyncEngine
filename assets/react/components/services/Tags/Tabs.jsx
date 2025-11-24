import React from 'react';
import { Tab, Tabs } from 'react-bootstrap';
import { objectToMappable } from '../../../utils/data';
import TagsItem from './Item';
import TagsItemStorage from './Storage';
import Icon from '../../partials/Icon';
import { useTranslation } from 'react-i18next';
import { ucfirst } from '../../../utils/globals';
import { isEmpty } from '../../../utils/conditions';

export default function TagsTabs( props ) {
	const { t } = useTranslation();

	const {
		tags,
	} = props;

	return (
		<Tabs>
			{
				objectToMappable( { ...tags }, '_tag', '_children', true ).filter( ( item ) => {
					if ( 'step' === item._tag ) {
						if ( isEmpty( item._children ) ) {
							return false;
						}
					}
					return true;
				} ).map( ( item, index ) => {
					let icon  = item.icon ?? item._tag;
					let title = t( item.title ?? ucfirst( item._tag ) );
					let component = <TagsItem { ...props } label={ item.label } tag={ item._tag } children={ item._children } open={ true } />;

					if ( '_storage' === item._tag ) {
						title = t( 'Storage' );
						icon = 'storage';
						component = <TagsItemStorage { ...props } tag="storage" />
					}

					return <Tab key={ index } eventKey={ item._tag } title={ <span className="p-2"><Icon icon={ icon } /> { title }</span> } >{ component }</Tab>
				} )
			}
		</Tabs>
	);
}
