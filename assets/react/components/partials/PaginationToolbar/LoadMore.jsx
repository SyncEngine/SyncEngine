import React from 'react';
import Button from '../Button';
import { useTranslation } from 'react-i18next';
import Icon from '../Icon';

export default function LoadMore( props ) {
	const { t } = useTranslation();

	const {
		callback,
		callbackReset,
		numItems,
		totalItems,
		variant,
		limit,
		size,
	} = props;

	const reset = callbackReset && limit && numItems > limit;

	if ( ! reset && numItems >= totalItems ) {
		return;
	}

	return (
		<div className={ "loadmore align-items-center pagination" + ( size ? ' pagination-' + size : '' ) }>
			<div className="page-item">
				<Button size={ size } className="page-link" onClick={ callback } variant={ variant } disabled={ numItems >= totalItems }>{ t('Load more') }</Button>
			</div>
			{ reset &&
				<div className="page-item d-flex align-items-center">
					<Icon
						icon="reset"
						className="page-link btn"
						onClick={ callbackReset }
					/>
				</div>
			}
		</div>
	)
}
