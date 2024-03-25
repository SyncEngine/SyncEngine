import React from 'react';
import Button from '../Button';
import { useTranslation } from 'react-i18next';

export default function LoadMore( props ) {
	const { t } = useTranslation();

	const {
		callback,
		numItems,
		totalItems,
		variant,
		size,
	} = props;

	if ( numItems >= totalItems ) {
		return;
	}

	return (
         <div className={ "loadmore pagination" + ( size ? ' pagination-' + size : '' ) }>
	         <Button size={ size } className="page-link" onClick={ callback } variant={ variant }>{ t('Load more') }</Button>
         </div>
	)
}
