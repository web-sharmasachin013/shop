// // Updated for React 18+
// import React from 'react';
// import { createRoot } from 'react-dom/client';

// // # Example 1: Simple "Hello, World" code
// const container = document.getElementById('react-app');
// const root = createRoot(container);
// root.render(<h1>Hello there - world!</h1>);



import React from 'react';
import { createRoot } from 'react-dom/client';
import Home from './Home';

const container = document.getElementById('react-app');
const root = createRoot(container);
root.render(<Home/>);
