/**
 * External dependencies
 */
import { createRoot } from "@wordpress/element";
import { API } from "@stoplight/elements";
import "@stoplight/elements/styles.min.css";

const elementsAppContainer = document.getElementById("elements-app");
const { fetch: originalFetch } = window;

function normalizeHeaders(headers) {
  if (headers instanceof Headers) {
    return headers;
  }

  // Convert plain object or array of key-value pairs to Headers
  return new Headers(headers);
}

window.fetch = (resource, config) => {
  // Initialize config
  config = config || {};

  // Always initialize headers
  config.headers = normalizeHeaders(config.headers || {});

  // Check if URL contains wp-json and add nonce
  if (resource.includes("wp-json")) {
    config.headers.set("X-WP-Nonce", window.openapi.nonce);
  }

  return originalFetch(resource, config);
};

const elements = (
  <API
    tryItCredentialsPolicy={"same-origin"}
    apiDescriptionUrl={window.openapi.endpoint}
    router={"hash"}
    layout={"sidebar"}
    hideTryIt={window.openapi.options.hideTryIt}
  />
);

// Create a root and render
const root = createRoot(elementsAppContainer);
root.render(elements);
