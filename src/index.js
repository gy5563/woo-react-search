import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App';
import './style.css';

document.addEventListener('DOMContentLoaded', () => {
    const rootElement = document.getElementById('woo-react-search-root');
    
    if (rootElement) {
        const root = createRoot(rootElement);
        root.render(<App />);
    }
});