import { useCallback, useRef } from 'react';
import useGlobal from './useGlobal';

/**
 * Serialize a value into a canonical string representation for consistent hashing.
 * @param {*} value
 * @returns {string}
 */
function serializeCanonical( value ) {
	if ( Array.isArray( value ) ) {
		return '[' + value.map( serializeCanonical ).join( ',' ) + ']';
	}

	if ( value && 'object' === typeof value ) {
		return '{' +
		       Object.keys( value )
		             .sort()
		             .map( key => key + ':' + serializeCanonical( value[ key ] ) )
		             .join( ',' )
		       + '}';
	}

	return JSON.stringify( value );
}

/**
 * Canonicalize query object for consistent hashing regardless of key order.
 * @param {*} key
 * @param {string} namespace
 * @returns {string}
 */
function parseCacheKey( key, namespace ) {
	if ( 'string' === typeof key ||  null === key ) {
		return key;
	}

	// Sort object keys for consistent hashing.
	if ( 'object' === typeof key ) {
		key = serializeCanonical( key );
	}

	return namespace + ':' + JSON.stringify( key );
}

/**
 * Pure generic cache hook.
 * Stores/retrieves values by key without knowing what the value represents.
 *
 * @param {string} type - Namespace for cache entries
 * @param {string|null} key - Unique cache key (can be any serializable type)
 * @param {number} [ttl=30000] - Cache TTL in milliseconds, default = 30 seconds
 */
export default function useCache( type, key, ttl = 30000 ) {
	const cacheStore = useGlobal( 'cache' );
	const cacheKey = useRef( parseCacheKey( key, type ) );

	// Check cache for this key
	const getCache = useCallback( () => {
		if ( ! cacheKey.current ) return null;

		const entry = cacheStore[ cacheKey.current ];
		if ( ! entry ) return null;

		// TTL expired
		if ( Date.now() - entry.timestamp > ttl ) {
			delete cacheStore[ cacheKey.current ];
			return null;
		}

		return entry.value;
	}, [ cacheKey.current, ttl ] );

	// Set cache for this key
	const setCache = useCallback( ( value ) => {
		if ( ! cacheKey.current ) return;

		cacheStore[ cacheKey.current ] = {
			value,
			timestamp: Date.now()
		};
	}, [ cacheKey.current ] );

	// Invalidate all caches for this type
	const invalidateAll = useCallback( () => {
		for ( const k in cacheStore ) {
			if ( k.startsWith(`${type}:`) ) {
				delete cacheStore[ k ];
			}
		}
	}, [ type ] );

	// Invalidate specific key
	const invalidateKey = useCallback( () => {
		if ( ! cacheKey.current ) return;
		delete cacheStore[ cacheKey.current ];
	}, [ cacheKey.current ] );

	// Invalidate caches matching a callback function
	const invalidateByCallback = useCallback( ( callback ) => {
		for ( const k in cacheStore ) {
			if ( k.startsWith(`${type}:`) && callback( k, cacheStore[ k ] ) ) {
				delete cacheStore[ k ];
			}
		}
	}, [ type ] );

	/**
	 * Update the key and optionally clean up old state
	 * @param {string} newKey
	 * @param {boolean} [cleanupOld=true] - Whether to delete the old cache entry
	 * @type {(function(*, boolean=): void)|*}
	 */
	const setKey = useCallback( ( newKey, cleanupOld = true ) => {
		newKey = parseCacheKey( newKey, type );
		if ( cacheKey.current === newKey ) return;
		if ( cleanupOld && cacheKey.current ) {
			delete cacheStore[ cacheKey.current ];
		}
		cacheKey.current = newKey;
	}, [ cacheKey.current, type ] );

	return { getCache, setCache, invalidateAll, invalidateKey, invalidateByCallback, setKey };
}
