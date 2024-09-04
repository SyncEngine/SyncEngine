/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */

// any CSS you import will output into a single css file (app.scss in this case)
import './styles/app.scss';

// Bootstrap JS
import * as bootstrap from 'bootstrap';

// Load internationalization.
import './i18n';

// start the Stimulus application.
import './bootstrap';

// Init SyncEngine.
import './global/init';
