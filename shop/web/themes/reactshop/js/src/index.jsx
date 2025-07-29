
import React from "react";
import { StrictMode } from "react";
import { createRoot } from "react-dom/client";

import "bootstrap";
import "bootstrap/dist/css/bootstrap.css";
import "bootstrap-icons/font/bootstrap-icons.css";
import "./index.css";

import App from "./App.jsx";
import store from "./app/store.js";
import { Provider } from "react-redux";
import { BrowserRouter } from "react-router-dom";

// Get the root element from the DOM
const rootElement = document.getElementById("react-app");

// Create a root and render the app
createRoot(rootElement).render(
  <StrictMode>
    <Provider store={store}>
      <BrowserRouter>
        <App />
      </BrowserRouter>
    </Provider>
  </StrictMode>
);
