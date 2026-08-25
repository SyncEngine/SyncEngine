import DOMPurify from 'dompurify';

/**
 * Sanitize HTML content for safe rendering with dangerouslySetInnerHTML.
 * Uses DOMPurify to strip dangerous tags, attributes, and protocols while
 * preserving safe formatting (bold, italic, links, tables, lists, etc.).
 *
 * @param {string} html - Raw HTML string to sanitize
 * @param {object} options - DOMPurify configuration overrides
 * @returns {string} Sanitized HTML
 */
export function sanitizeHtml( html, options = {} ) {
	if ( ! html || typeof html !== 'string' ) {
		return '';
	}

	const defaultOptions = {
		USE_PROFILES: { html: true },
	};

	return DOMPurify.sanitize( html, options ?? defaultOptions );
}

/**
 * Sanitize SVG content specifically. Strips script tags and event handlers
 * while preserving valid SVG presentation attributes.
 *
 * @param {string} svg - Raw SVG string
 * @returns {string} Sanitized SVG
 */
export function sanitizeSvg( svg ) {
	if ( ! svg || typeof svg !== 'string' ) {
		return '';
	}

	return DOMPurify.sanitize( svg, {
		USE_PROFILES: { svg: true, svgFilters: true },
	} );
}
