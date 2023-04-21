import * as React from 'react';

// 1. import `ChakraProvider` component
import { ChakraProvider } from '@chakra-ui/react';

/**
 * @todo Maybe load without default theme?
 * @link https://chakra-ui.com/getting-started#chakrabaseprovider
 */
// import { ChakraBaseProvider, extendBaseTheme } from '@chakra-ui/react';
// import chakraTheme from '@chakra-ui/theme';

/**
 * @todo Or just extend the default theme.
 * @link https://chakra-ui.com/getting-started/cra-guide#customizing-theme
 */
// import { extendTheme, ChakraProvider } from '@chakra-ui/react'

export function Loader( context ) {
	return (
		<ChakraProvider>
			<p>Chakra App: {context.scope.identifier}</p>
		</ChakraProvider>
	);
}
