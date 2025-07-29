import React from 'react';

import { createRoot } from 'react-dom/client';

const container = document.getElementById('react-app');
const root = createRoot(container);

root.render(
  <h1>Hello there - world! test</h1>
);
