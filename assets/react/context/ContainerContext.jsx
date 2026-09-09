import { createContext } from 'react';

/**
 * Context for container ref in modal/dialog components.
 * Provides access to the container DOM element for focus management.
 * @type {import('react').Context<React.RefObject<HTMLElement> | null>}
 */
export const ContainerContext = createContext( null );
