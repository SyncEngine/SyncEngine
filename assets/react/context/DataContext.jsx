import { createContext } from 'react';

/**
 * Context for data payload in workflow components.
 * Optional — components should handle null gracefully.
 * @type {import('react').Context<Object<string, any> | null>}
 */
export const DataContext = createContext( null );
