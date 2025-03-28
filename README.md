# HeadlessWP

A WordPress plugin for headless WordPress functionality.

## Development Setup

1. Clone the repository:

```bash
git clone [repository-url]
cd headlesswp
```

2. Install Node.js dependencies:

```bash
npm install
```

## Development Process

### Working with Assets

The plugin uses WordPress Scripts for asset compilation. Source files are located in:

- JavaScript: `assets/js/`
- Styles: `assets/css/`

During development, run:

```bash
npm run start
```

This will watch for changes in your assets and recompile them automatically.

### Building for Production

To build assets for production:

```bash
npm run build
```

This will create minified files in the `build/` directory:

- `build/js/openapi.js`
- `build/css/openapi.css`

#### JavaScript Dependencies

- Production:
  - @stoplight/elements: ^7.6.1
  - @wordpress/element: ^4.11.0
- Development:
  - @seriousme/openapi-schema-validator: ^2.0.2
  - @woocommerce/dependency-extraction-webpack-plugin: 1.4.0
  - @wordpress/scripts: ^13.0.3

### Distribution Process

1. Build the production assets:

```bash
npm run build
```

2. Create a distribution copy excluding development files:

```bash
# Files/directories to exclude
- node_modules/
- vendor/
- .git/
- .github/
- assets/
- tests/
- .gitignore
- package.json
- package-lock.json
- composer.json
- composer.lock
- webpack.config.js
```

## Features

- OpenAPI Documentation Generation
- CORS Support
- Headless Mode Configuration

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

GPL-2.0-or-later
