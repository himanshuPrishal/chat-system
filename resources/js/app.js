import './bootstrap';

import Alpine from 'alpinejs';
import { webrtcCall } from './components/webrtc';

window.Alpine = Alpine;
window.webrtcCall = webrtcCall;

Alpine.start();
