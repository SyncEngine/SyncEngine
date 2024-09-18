import * as bootstrapIcons from 'bootstrap-icons/font/bootstrap-icons.json';

Object.keys( bootstrapIcons ).forEach(( key ) => {
	SyncEngine.icons[ 'bi-' + key ] = 'bi bi-' + key;
})
