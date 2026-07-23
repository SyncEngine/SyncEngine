<?php

namespace SyncEngine\Webservice\Type;

class SoapWebserviceType extends GenericWebserviceType
{
	const TYPE        = 'soap';
	const NAME        = 'SOAP';
	const DESCRIPTION = 'SOAP webservice';

	// SOAP version constants for config use.
	const SOAP_1_1 = '1.1';
	const SOAP_1_2 = '1.2';

	// Compression constants for config use.
	const COMPRESSION_NONE   = 'none';
	const COMPRESSION_GZIP   = 'gzip';
	const COMPRESSION_DEFLATE = 'deflate';

	// WSDL cache constants for config use.
	// These mirror PHP's native WSDL_CACHE_* constants.
	const CACHE_NONE    = 'none';
	const CACHE_MEMORY  = 'memory';
	const CACHE_FILE    = 'file';
	const CACHE_BOTH    = 'both';
}
